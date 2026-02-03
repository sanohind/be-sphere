<?php

namespace App\Repositories\OAuth;

use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use App\Entities\OAuth\UserEntity;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use League\OAuth2\Server\Entities\UserEntityInterface;

class UserRepository implements UserRepositoryInterface
{
    /**
     * Get a user entity.
     *
     * @param string $username
     * @param string $password
     * @param string $grantType
     * @param ClientEntityInterface $clientEntity
     *
     * @return UserEntityInterface|null
     */
    public function getUserEntityByUserCredentials(
        $username,
        $password,
        $grantType,
        ClientEntityInterface $clientEntity
    ) {
        $user = User::where('email', $username)
                    ->orWhere('username', $username)
                    ->first();

        if (!$user) {
            return null;
        }

        if (Hash::check($password, $user->password)) {
            return new UserEntity($user->id);
        }

        return null;
    }
}
