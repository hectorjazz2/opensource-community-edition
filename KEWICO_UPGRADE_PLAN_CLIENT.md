# Kewico System Upgrade: Plan and Budget

Date: 27 September 2026

## 1. Summary

| | |
| --- | --- |
| Duration | about 3 months; finished result shown **before 24 December 2026** |
| Expected effort | **270 hours**, with 15–25 hours of work per week |
| Maximum for the whole upgrade | **280 hours**. This limit is not exceeded without your written approval |
| Optional extra functions (Kanban, Milestones) | 8 hours, only if you want them, done after the presentation (the maximum stays 280 hours) |
| Your current system | production and test servers keep running **unchanged** until the switch |
| Budget | see section 8 at the end |

### What the upgrade means

The **new version of the software**, built on the modern **CakePHP 4** framework, is taken as the base, and **all Kewico functions** from your current system are moved into it.

| | Technical foundation | Kewico functions |
| --- | --- | --- |
| **Current system** | CakePHP 2 (old, no longer supported) | All Kewico functions |
| **New system** | **CakePHP 4 (modern, supported)** | **The same Kewico functions, moved over** |

**Result: new system = modern CakePHP 4 foundation + all your Kewico functions.**

Your team keeps working with the functions it knows. Only the foundation underneath is replaced.

## 2. Why upgrade?

### What you gain

- **Security.** The current system is built on a technical foundation that no longer receives security fixes. The new version is actively maintained.
- **Keeping the system running.** Hosting providers regularly retire old PHP versions. The current system already needs special patches to run. A hosting update could stop it from working, and a rushed emergency move costs more than a planned one.
- **Cheaper changes later.** In the current system, one change often affects other parts, so small changes take longer. The new structure is cleaner, so future work for Kewico gets faster and cheaper.
- **Free improvements.** The makers of the software keep improving the new version, for example Kanban boards, calendar views, recurring tasks and a modern project view. Kewico receives these improvements too.

### What you will not notice immediately

On day one your team sees **mostly the same system with the same features**. The upgrade is an investment in security, reliability and lower future costs, not a set of new business features.

## 3. Words used in this document

Kewico uses its own names for things. Kewico's names are kept:

| Kewico name (what your team sees) | Name in the standard software |
| --- | --- |
| **Account** | Project |
| **Project** | Task |

The new version uses the standard names at first. Step 6 changes them back to Kewico's names in all 10 languages.

## 4. Your current system and weekly working time

- The **production server** (https://kewico.com/APP/CAD/) and the **test server** (https://test.kewico.com/) keep running normally during the whole upgrade.
- Every fix made on the current system during the upgrade is also added to the new system, so the problem does not come back after the switch.

| | |
| --- | --- |
| Time for the upgrade | **15–25 hours per week** (about 21 hours on average) |
| Maintenance of the current Kewico system | **always has high priority**: issues are fixed first, then the upgrade work continues |

- In a week with more maintenance work, fewer hours go into the upgrade, and the dates in section 6 can move a little. You will be informed when this happens.
- The dates in section 6 are based on about 21 hours per week.

## 5. The preview

| | Address |
| --- | --- |
| Current system (live, unchanged) | https://kewico.com/APP/CAD/ |
| New system (preview) | https://kewico.com/APP/CAD_NEW/ |

- The preview uses a **copy** of your data. Changes made there do not affect the live system.
- It **does not send** emails or push notifications to your users.
- Log in with your usual email and password.
- Parts appear step by step, following the table in section 6.

## 6. The steps

Each step rebuilds one part of the system. The dates assume about 21 hours of work per week, starting 28 September 2026.

| Step | Part of the system | What it covers | Hours | Ready in preview by |
| --- | --- | --- | --- | --- |
| **1** | **Data move and preview** | All your data copied into the new system and checked: 129 users, 29 accounts, 253 projects, 339 files, all time logs and invoices. The preview opens at kewico.com/APP/CAD_NEW/ | 40 | 9 Oct 2026 |
| **2** | **Users** | User list, inviting new users, registration page, profiles, business units, contact card (vCard) download, user export | 12 | 14 Oct 2026 |
| **3** | **Accounts** | Account cards (billable/non-billable, users, milestones, dates), active/inactive accounts, project status groups (e.g. "Salesplan"), customers, external customers, salesmen and coordinators | 12 | 19 Oct 2026 |
| **4** | **User roles and permissions** | Who can see and do what, including access to the AI Compliance Checker | 10 | 21 Oct 2026 |
| **5** | **Projects** | Project list with tabs (All, My Projects, Assigned, Overdue), filters, group by, sort and hidden fields, status colours (New, In Progress, Information Pending, In Revision); project details with comments (public/internal), files, checklist, status report, time log, reminders, subtasks and project linking; due-date rules (holidays, weekends); email notifications; archive; labels | 32 | 2 Nov 2026 |
| **6** | **Languages and Kewico wording** | **Language Settings:** each user chooses their language (Chinese, Dutch, English, French, German, Italian, Portuguese, Romanian, Spanish, Turkish). **Translation File** in Company Settings: download and upload the translations. All screens available in these 10 languages, including the new screens. "Project" becomes "Account" and "Task" becomes "Project" everywhere | 16 | 6 Nov 2026 |
| **7** | **Time Log** | Booking time, the timer, paid/unpaid tracking, payment invoices, payment emails | 18 | 12 Nov 2026 |
| **8** | **Calendar** | My Calendar and Team Calendar (week and month view), events and projects per person with daily and weekly totals, company holidays and weekends, Add Leave, Add Event, Approvals, Calendar Setting, reminders and alarms | 22 | 19 Nov 2026 |
| **9** | **Invoice** | Invoice overview, viewing and creating invoices, invoice settings, PDF layout | 20 | 26 Nov 2026 |
| **10** | **Dashboard and Analytics** | Dashboard figures (accounts, active users, closed/total projects), Account RAG Status, To Do List, Account Progress Report, Analytics | 6 | 27 Nov 2026 |
| **11** | **AI Compliance Checker** | Rule sets, reference files, compliance checks and reports | 20 | 4 Dec 2026 |
| **12** | **Mobile app** | Everything the mobile app uses, plus push notifications on phones. The mobile app keeps working without an update | 36 | 17 Dec 2026 |
| **13** | **Other parts** | Files, Documents, Daily Catch-Up, Templates, Workflow Management, Archive, Archicad translations, backups | 10 | 21 Dec 2026 |
| **14** | **Presentation, test week and switch** | Presentation of the finished system (**before 24 Dec**), your team's test week, the switch and the first week after it (January 2027) | 16 | Presentation 22 Dec 2026 |
| | **Total** | | **270** | |

**Optional:**

| Step | Part of the system | What it covers | Hours |
| --- | --- | --- | --- |
| A | **Kanban** | The board view is included in the new version. It is checked and adjusted to Kewico's setup | 4 |
| B | **Milestones** | Included in the new version. It is checked and adjusted to Kewico's setup | 4 |

The optional steps are done after the presentation, in January 2027, so the presentation date does not move.

## 7. The switch (January 2027)

The switch takes one evening or a weekend, on a date agreed together:

1. Users are informed of a short maintenance break.
2. The latest data is moved into the new system and checked.
3. The new system takes over the normal address, https://kewico.com/APP/CAD/.
4. For the first 48 hours the system is watched closely.

**Safety net:** the old system is kept, untouched. If a serious problem appears in the first 48 hours, the system is switched back to it.

## 8. Budget

| | Hours | Cost |
| --- | --- | --- |
| **Expected total** | 270 | **€9,450** |
| **Maximum for the whole upgrade** | 280 | **€9,800** |
| Optional extras (Kanban, Milestones) | 8 | €280 |
| Expected total including optional extras | 278 | €9,730 |

- **Expected:** the planned number of hours.
- **Maximum:** the limit for the whole upgrade, also with the optional extras. It is not exceeded without your written approval.
- **Payment:** step by step, only for finished steps.

**Not included:** new features or changes requested during the upgrade (quoted separately before any work starts), support for the current servers (see section 4), and hosting costs if the hosting package needs an upgrade.
