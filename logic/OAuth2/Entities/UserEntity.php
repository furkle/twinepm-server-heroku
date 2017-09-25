<?php
namespace TwinePM\OAuth2\Entities;

use \League\OAuth2\Server\Entities\UserEntityInterface;
class UserEntity implements UserEntityInterface {
    private $id;

    public function __construct(int $id) {
        $this->id = $id;
    }

    public function getIdentifier() {
        return $this->id;
    }
}
?>