<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\PostShare;

class PostSharePolicy
{
    public function update(Account $account, PostShare $postShare): bool
    {
        return (int) $postShare->account_id === (int) $account->id;
    }

    public function delete(Account $account, PostShare $postShare): bool
    {
        return (int) $postShare->account_id === (int) $account->id || $account->isAdmin();
    }
}
