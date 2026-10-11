# Core 3 live presentation setup

The deployed application starts with the baseline records, not the localhost rehearsal dataset. The presentation setup is an explicit Super Admin action. It is never run by container startup and never imports a local SQL dump.

## Before applying

1. Confirm the latest deployment is healthy and sign in as Super Admin.
2. Open **Database Backup** and click **Export Database SQL Dump**. Securely save the downloaded file. It contains account hashes and employee data.
3. On the same page, enter the current Super Admin password, type `PREPARE CORE3 DEMO`, and click **Prepare Demo Records** within 30 minutes of the backup request.
4. Copy the one-time passwords from the result. Each new account has a distinct random password and starts **Disabled**. Do not put the passwords in a slide, public repository, or class chat.
5. Open **User Accounts**. Activate only the demo accounts needed for the presentation. Turn them off again after the rehearsal or defense.

The action is additive and can be retried after a failed transaction. It does not reset existing passwords or change the four original login accounts. A repeated successful run will not reveal previously generated passwords; use Super Admin's password reset action if needed.

## New presentation records

| Account | Scenario |
| --- | --- |
| `demo.mark` | 42% failed assessment, skill gap, TNA recommendation |
| `demo.john` | Expired SMAW certificate |
| `demo.liza` | Valid SMAW certificate, probationary benefit comparison |
| `demo.angela` | Expiring contract and documents |
| `demo.juan` | Active employee, submitted benefit claim |
| `demo.paolo` | Active loan with one recorded installment |
| `demo.carlo` | Unreturned laptop and blocked exit clearance |
| `demo.it_approver` | Assigned IT clearance responsibility |
| `demo.finance_approver` | Assigned Finance clearance responsibility |

All new employee codes start with `DEMO-C3-`; the extra fictional employee **Ava Flores** (`DEMO-C3-007`) has a completed clearance and **Ready for Payroll** status for the positive separation comparison. Ava has no login account; show her record through HR. The benefit comparison uses **CORE3 DEMO - Medical Allowance** so it cannot overwrite an existing plan. Supporting documents use a generated fictional PDF.

## Check before presenting

Use Super Admin to confirm the new users exist and are activated as needed. Then sign in as HR Manager and check Mark in Training Needs Analysis, John versus Liza in Certification Registry, Angela in Contracts, Juan and Liza against the same benefit plan, Paolo's loan, Carlo's blocked clearance, and Ava's completed clearance. For document return and resubmission, use the normal employee and HR workflows; the setup only supplies starting document states. Fresh certificate completion and issuance is a separate workflow test; the setup does not fabricate a generated certificate or verification code.

Existing documentation and screenshots from localhost may use Clarissa as the completed case and may cite fixed record IDs or dates. On the live site use **Ava Flores** for that comparison, and search by employee name rather than copying localhost IDs. Dates in the presentation records are relative to the day setup runs.

## If the action fails

The database transaction rolls back and new accounts remain unavailable. Check HostForge Runtime Logs for `Core 3 presentation setup failed` and resolve the stated prerequisite. Do not run **Restore Enterprise Data** to retry. Preserve the backup and investigate before reapplying.
