<?php

namespace App\Actions\Profiles;

use App\Models\MasterProfile;
use App\Models\MasterProfileRole;
use Illuminate\Support\Facades\DB;

/**
 * Folds duplicate profiles into one: roles (settings merged, primary
 * wins), linked customers / suppliers / loans / users, ledger lines
 * tagged to the profile, documents, notes, kisti plans and payroll move to
 * the primary; the duplicates are then soft-deleted.
 */
class MergeProfiles
{
    public function handle(MasterProfile $primary, array $duplicateIds): int
    {
        $ids = collect($duplicateIds)->map(fn ($id) => (int) $id)->reject(fn ($id) => $id === $primary->id)->unique()->values()->all();

        if (! $ids) {
            return 0;
        }

        return DB::transaction(function () use ($primary, $ids) {
            foreach (MasterProfileRole::whereIn('master_profile_id', $ids)->get() as $role) {
                $mine = $primary->roles()->where('role', $role->role)->first();
                $mine
                    ? $mine->update(['settings' => array_merge($role->settings ?? [], $mine->settings ?? [])])
                    : $primary->roles()->create(['role' => $role->role, 'settings' => $role->settings]);
            }
            MasterProfileRole::whereIn('master_profile_id', $ids)->delete();

            foreach (['customers', 'suppliers', 'loans', 'users', 'master_profile_documents', 'master_profile_activities', 'installment_plans', 'employee_advances'] as $table) {
                DB::table($table)->whereIn('master_profile_id', $ids)->update(['master_profile_id' => $primary->id]);
            }

            DB::table('payroll_items as pi')->whereIn('pi.master_profile_id', $ids)
                ->whereNotExists(fn ($q) => $q->from('payroll_items as x')->whereColumn('x.payroll_run_id', 'pi.payroll_run_id')->where('x.master_profile_id', $primary->id))
                ->update(['master_profile_id' => $primary->id]);

            DB::table('journal_entry_lines')->where('subledger_type', MasterProfile::class)->whereIn('subledger_id', $ids)
                ->update(['subledger_type' => MasterProfile::class, 'subledger_id' => $primary->id]);

            $names = MasterProfile::whereIn('id', $ids)->pluck('code')->implode(', ');
            MasterProfile::whereIn('id', $ids)->update(['updated_by' => auth()->id()]);
            MasterProfile::whereIn('id', $ids)->get()->each->delete();

            $primary->unsetRelation('roles');
            $primary->logActivity('merge', "Merged duplicate profile(s): {$names}");

            return count($ids);
        });
    }
}
