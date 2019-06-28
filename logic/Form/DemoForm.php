<?php
declare(strict_types=1);

namespace Logic\Form;

use Logic\Model\User;

class DemoForm extends BaseForm
{
    public function handle()
    {
        $user = User::query()->first();
        return $user->name ?: 'aaa';
    }
}
