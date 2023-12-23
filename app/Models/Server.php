<?php

namespace App\Models;

use App\Contracts\FileProviderInterface;
use App\Contracts\LogProviderInterface;
use App\Events\ServerUpdated;
use App\Exceptions\ServerHandler;
use App\Infrastructure\Entities\ServerStatus;
use App\Infrastructure\Entities\ServerType;
use App\Infrastructure\ProviderFactory;
use App\Infrastructure\ServerProvider;
use App\Jobs\CreateServerOnInfrastructure;
use App\Jobs\ProvisionServer;
use App\Jobs\WaitForServerToConnect;
use App\Models\Task as TaskModel;
use App\Provider;
use App\Server\Database\DatabaseManager;
use App\Server\Database\MySqlDatabase;
use App\Server\PhpVersion;
use App\Server\ServerFiles;
use App\Server\SoftwareEnum;
use App\Tasks\Task;
use App\Tasks\UploadFile;
use App\Tasks\Whoami;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\HtmlString;
use ProtoneMedia\LaravelTaskRunner\Connection;
use ProtoneMedia\LaravelTaskRunner\PendingTask;

/**
 * @property EloquentCollection $crons
 * @property EloquentCollection $daemons
 * @property EloquentCollection $firewallRules
 * @property Team $team
 * @property Credentials|null $credentials
 * @property User|null $createdByUser
 */
class Server extends Model implements FileProviderInterface, LogProviderInterface
{
    use HasUlids;

    protected $fillable = [
        'name',
        'credentials_id',
        'region',
        'type',
        'image',
        'public_ipv4',
        'ssh_port',
        'team_id',
        'created_by_user_id',
        'provider',
        'public_key',
        'private_key',
        'working_directory',
        'username',
        'password',
        'database_password',
    ];

    protected $casts = [
        'add_github_ssh_key' => 'boolean',
        'completed_provision_steps' => AsArrayObject::class,
        'cpu_cores' => 'integer',
        'database_password' => 'encrypted',
        'installed_software' => AsArrayObject::class,
        'memory_in_mb' => 'integer',
        'password' => 'encrypted',
        'private_key' => 'encrypted',
        'provider' => Provider::class,
        'provisioned_at' => 'datetime',
        'public_key' => 'encrypted',
        'ssh_port' => 'integer',
        'status' => ServerStatus::class,
        'storage_in_gb' => 'integer',
        'uninstallation_requested_at' => 'datetime',
        'user_public_key' => 'encrypted',
    ];

    protected $dispatchesEvents = [
        'updated' => ServerUpdated::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (Server $server) {
            $server->status ??= ServerStatus::New;
            $server->completed_provision_steps ??= [];
            $server->installed_software ??= [];
        });

        //        static::deleted(function ($server) {
        //            event(new ServerDeleted($server->id, $server->team_id));
        //        });
    }

    /**
     * Returns the name with the IP address appended.
     */
    public function getNameWithIpAttribute(): string
    {
        return "$this->name ($this->public_ipv4)";
    }

    /**
     * Returns the name of the provider.
     */
    public function getProviderNameAttribute(): string
    {
        return $this->provider->name;
    }

    /**
     * Returns the formatted status of the server.
     */
    public function getStatusNameAttribute(): string
    {
        return $this->status->name;
    }

    public function softwares(): HasMany
    {
        return $this->hasMany(Software::class);
    }

    /**
     * Returns a boolean indicating if the server has the given software installed.
     */
    public function softwareIsInstalled(SoftwareEnum $software): bool
    {
        return $this->installedSoftware()->contains($software);
    }

    /**
     * Returns a Collection of Software enums that are installed on the server.
     */
    public function installedSoftware(): Collection
    {
        return $this->installed_software->collect()->map(function ($software) {
            return SoftwareEnum::from($software);
        });
    }

    /**
     * Returns a key-value Collection of installed PHP versions.
     */
    public function installedPhpVersions(): array
    {
        return $this->installed_software->collect()->map(function ($software) {
            return SoftwareEnum::from($software)->findPhpVersion();
        })->filter()->pipe(function ($phpVersions) {
            return PhpVersion::named($phpVersions->all());
        });
    }

    /**
     * Returns an instance of the Infrastructure Provider.
     *
     * @throws Exception
     */
    public function getProvider(): ServerProvider
    {
        return app(ProviderFactory::class)->forServer($this);
    }

    /**
     * Updates the model by the ServerType entity.
     */
    public function updateType(ServerType $type): self
    {
        $this->forceFill([
            'cpu_cores' => $type->cpuCores,
            'memory_in_mb' => $type->memoryInMb,
            'storage_in_gb' => $type->storageInGb,
            'type' => $type->id,
        ])->save();

        return $this;
    }

    /**
     * A bash provision command used to start the provision of custom servers.
     */
    public function provisionCommand(): HtmlString
    {
        return new HtmlString("wget --no-verbose -O - {$this->provisionScriptUrl()} | bash");
    }

    /**
     * Returns a signed URL that containts the script to connect a custom server to the app.
     */
    public function provisionScriptUrl(): string
    {
        return URL::relativeSignedRoute('servers.provision-script', ['server' => $this]);
    }

    /**
     * Returns a boolean indicating if we can connect to the server.
     */
    public function canConnectOverSsh(): bool
    {
        if (! $this->public_ipv4) {
            return false;
        }

        try {
            $result = $this->runTask(Whoami::class)->asRoot()->dispatch();
        } catch (CouldNotConnectToServerException $e) {
            return false;
        }

        return $result && $result->isSuccessful() && $result->getBuffer() === 'root';
    }

    /**
     * Returns an instance of the TaskDispatcher with the given task and this server as target.
     */
    public function runTask(string|Task|PendingTask $task): ServerTaskDispatcher
    {
        return new ServerTaskDispatcher($this, PendingTask::make($task));
    }

    /**
     * Returns a Connection instance to connect to the server as root.
     */
    public function connectionAsRoot(): Connection
    {
        return new Connection(
            host: $this->public_ipv4,
            port: $this->ssh_port,
            username: 'root',
            privateKey: $this->private_key,
            scriptPath: "/root/$this->working_directory",
            proxyJump: self::randomSshProxy()
        );
    }

    /**
     * Returns a random IP address from the configuration that can be
     * used as a SSH proxy.
     */
    private static function randomSshProxy(): ?string
    {
        $proxies = array_filter(config('eddy.ssh_proxies', []));

        return count($proxies) > 0 ? Arr::random($proxies) : null;
    }

    /**
     * Returns a Connection instance to connect to the server as a non-root user.
     */
    public function connectionAsUser(?string $username = null): Connection
    {
        $username = $username ?? $this->username;

        return new Connection(
            host: $this->public_ipv4,
            port: $this->ssh_port,
            username: $username,
            privateKey: $this->private_key,
            scriptPath: "/home/$username/$this->working_directory",
            proxyJump: self::randomSshProxy()
        );
    }

    /**
     * Dispatches a chain of jobs to provision the server.
     */
    public function dispatchCreateAndProvisionJobs(Collection $sshKeys): void
    {
        $server = $this->fresh();

        $jobs = [
            new CreateServerOnInfrastructure($server),
            new WaitForServerToConnect($server),
            new ProvisionServer($server, EloquentCollection::make($sshKeys)),
        ];

        Bus::chain($jobs)->dispatch();
    }

    /**
     * Uploads the given file to the server as root.
     */
    public function uploadAsRoot(string $path, string $contents, bool $throw = false): bool
    {
        $task = new UploadFile($path, $contents);

        return $this->runTask($task)->asRoot()->throw($throw)->dispatch()->isSuccessful();
    }

    /**
     * Uploads the given file to the server as a non-root user.
     */
    public function uploadAsUser(string $path, string $contents, ?string $username = null, bool $throw = false): bool
    {
        $task = new UploadFile($path, $contents);

        return $this->runTask($task)->asUser($username)->throw($throw)->dispatch()->isSuccessful();
    }

    /**
     * Returns an instance of the DatabaseManager for this server.
     *
     * @throws BindingResolutionException
     */
    public function databaseManager(): DatabaseManager
    {
        return app()->makeWith(MySqlDatabase::class, ['server' => $this]);
    }

    public function logFiles(): array
    {
        return $this->files()->logFiles()->toArray();
    }

    public function editableFiles(): array
    {
        return $this->files()->editableFiles()->toArray();
    }

    /**
     * Returns an instance of ServerFiles to manage files on this server.
     */
    public function files(): ServerFiles
    {
        return new ServerFiles($this);
    }

    /**
     * Returns an exception handler for this server.
     */
    public function exceptionHandler(): ServerHandler
    {
        return new ServerHandler($this);
    }

    //

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function credentials(): BelongsTo
    {
        return $this->belongsTo(Credentials::class);
    }

    public function githubCredentials(): BelongsTo
    {
        return $this->belongsTo(Credentials::class, 'github_credentials_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(TaskModel::class);
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    public function backups(): HasMany
    {
        return $this->hasMany(Backup::class)
            ->orderBy((new Backup)->qualifyColumn('name'));
    }

    public function crons(): HasMany
    {
        return $this->hasMany(Cron::class);
    }

    public function daemons(): HasMany
    {
        return $this->hasMany(Daemon::class);
    }

    public function databases(): HasMany
    {
        return $this->hasMany(Database::class)
            ->orderBy((new Database)->qualifyColumn('name'));
    }

    public function databaseUsers(): HasMany
    {
        return $this->hasMany(DatabaseUser::class)
            ->orderBy((new DatabaseUser)->qualifyColumn('name'));
    }

    public function firewallRules(): HasMany
    {
        return $this->hasMany(FirewallRule::class);
    }
}
