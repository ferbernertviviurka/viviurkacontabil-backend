<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Master can view all companies
        // Normal users can view companies (filtered in controller)
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Company $company): bool
    {
        // Master can view any company
        if ($user->isMaster()) {
            return true;
        }

        // Normal users can only view their own company
        return $user->company_id === $company->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only master can create companies
        return $user->isMaster();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Company $company): bool
    {
        // Master can update any company
        if ($user->isMaster()) {
            return true;
        }

        // Normal users can update their own company
        return $user->company_id === $company->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Company $company): bool
    {
        // Only master can delete companies
        return $user->isMaster();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Company $company): bool
    {
        // Only master can restore companies
        return $user->isMaster();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Company $company): bool
    {
        // Only master can force delete companies
        return $user->isMaster();
    }
}
