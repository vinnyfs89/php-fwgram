<?php

try {

    require __DIR__ . '/../config.php';

    $userNameSearched = getenv('USERNAME_SEARCHED');

    if(is_null($userNameSearched) || empty($userNameSearched)) {
        throw new Exception("You need to define `USERNAME_SEARCHED` in .env file");
    }

    $backupDataFolder = getenv('BACKUP_DATA_FOLDER');
    if(is_null($backupDataFolder) || empty($backupDataFolder)) {
        throw new Exception("You need to define `BACKUP_DATA_FOLDER` in .env file");
    }

    $info = $instagramAPI->people->getInfoByName($userNameSearched);
    file_put_contents("{$backupDataFolder}/{$userNameSearched}_infos.json", json_encode($info));
    file_put_contents("{$backupDataFolder}/{$userNameSearched}_infos.txt", print_r($info, true));

    $followers = [
        'users' => [],
        'count' => 0
    ];

    $maxId = null;
    $rankToken = \InstagramAPI\Signatures::generateUUID();
    $searchQuery = null;

    do {
        $response = $instagramAPI->people->getFollowers(
            $info->getUser()->getPk(),
            $rankToken,
            $searchQuery,
            $maxId
        );
        $apiUsers = $response->getUsers();
        foreach ($apiUsers as $key => $userModel) {
            /**
             * @var $userModel \InstagramAPI\Response\Model\User
             */
            end($followers['users']);
            $userIndex = (int)key($followers['users']) + 1;
            reset($followers['users']);

            $followers['users'][$userIndex]['pk'] = $userModel->getPk();
            $followers['users'][$userIndex]['username'] = $userModel->getUsername();
            $followers['users'][$userIndex]['full_name'] = $userModel->getFullName();
        }
        $followers['count'] += count($apiUsers);
        $followers['nextMaxId'] = $response->getNextMaxId();
        $maxId = $followers['nextMaxId'];
    } while ($maxId !== null);

    file_put_contents("{$backupDataFolder}/{$userNameSearched}_followers.json", json_encode($followers));
    file_put_contents("{$backupDataFolder}/{$userNameSearched}_followers.txt", print_r($followers, true));

    $instagramAPI->logout();

    print "\n=== [ Backup of Followers - Complete! ] ===\n";

} catch (\Exception $e) {
    echo 'Something went wrong: ' . $e->getMessage() . "\n";
}
