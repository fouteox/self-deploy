<x-task-shell-defaults/>

sed -i 's#{{ $publicKey }}##' /home/{{ $server->username }}/.ssh/authorized_keys
sed -i '/^$/d' /home/{{ $server->username }}/.ssh/authorized_keys
