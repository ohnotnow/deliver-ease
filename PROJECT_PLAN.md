# DeliverEase

## Original Concept

I want to create a mobile-focussed app called 'DeliverEase'.  It's main purpose is to allow small, local businesses
which do their own deliveries keep their customers notified about deliveries which are out that day.

On the business owner screen - they should be able to create a new 'delivery run'.  This gives them a text box
where they can paste in a list of email addresses - one per line - and a name to go with it (the name could be an address, the customer name - whatever - and is optional).  And there should be a regular text box so that
they can give this 'run' a name of their choosing.

On their main screen there should be a list of 'runs' with the
name, the date it was created, and an edit button which takes them to the pre-filled data in the same screen as the
one to create a new run.

They should also have a 'share' button next to each run which will allow them to give the 'run' to a driver.  The
share link should use a unique UUID style to make it difficult to guess.

On the delivery driver side - they are given the share link and when they open it they see a simple screen which is
a little like a 'TODO' app.  The controls should be large and easy to use for a rushed, busy driver using a mobile phone.  There is a friendly list of the name (if provided) with a fallback of the email addresses and a 'Complete' button next to each.  Above the list
there should also be a 'Start' button.

When the driver clicks the 'Start' button - an email will be sent to the first and second people on the 'run'.  This will
notify them that they are next for delivery - or one stop away respectively.

Then when the driver marks the first delivery as completed.  That visually
marks the delivery as done and also emails the third person on the list to let them know they are one stop away.

This process continues until the whole list is processed.

On the admin page they should be able to see the list and the completed indicator - so they can keep track of how the deliveries
are going.

---

## Architecture Decisions

- Users belong to a Business entity, Runs belong to Business
- Driver links require UUID + PIN (set by business owner)
- Business owner IS the admin who tracks their own runs
- Use Laravel events/listeners for notification logic (extensible)
- Dynamic form fields for delivery entry (Name + Email per row)
- **Livewire v4 beta** - Upgraded to test new features (wire:sort for drag-and-drop)

---

## Implementation Progress

### Phase 1: Database & Models - COMPLETE

- [x] **1.1 Update Migrations**
  - `businesses` - Added `name`
  - `users` - Added `business_id` foreign key (migration: `2025_12_06_115126_add_business_id_to_users_table.php`)
  - `runs` - Added all fields (uuid, business_id, created_by_user_id, name, pin, status, started_at, completed_at)
  - `deliveries` - Created new table (migration: `2025_12_06_115150_create_deliveries_table.php`)

- [x] **1.2 Create Enums**
  - `App\Enums\RunStatus` - Pending, InProgress, Completed
  - `App\Enums\DeliveryStatus` - Pending, Notified, Completed

- [x] **1.3 Update Models**
  - `Business` - fillable: name, relationships: users(), runs()
  - `User` - Added business_id to fillable, relationship: business()
  - `Run` - All fields, auto-generate UUID on creating, relationships, helper methods
  - `Delivery` - All fields, **scopes for pending/notified/completed**, display_name accessor
  - **Note:** Added scopes to Delivery model so Run can use cleaner queries like `$this->deliveries()->completed()->count()`

- [x] **1.4 Create Factories & Update Seeder**
  - BusinessFactory, RunFactory (with inProgress/completed states), DeliveryFactory (with notified/completed/withoutName states)
  - TestDataSeeder populated with sample business, user, and runs

### Phase 2: Core Logic (Events & Listeners) - COMPLETE

- [x] **2.1 Events**
  - `RunStarted` - dispatched from `Run::start()` method
  - `DeliveryCompleted` - dispatched from `Delivery::markAsCompleted()` method

- [x] **2.2 Listeners**
  - `SendInitialNotifications` - handles RunStarted, emails positions 1 and 2
  - `SendNextDeliveryNotifications` - handles DeliveryCompleted, cascades notifications, marks run complete when done
  - **Note:** Laravel 12 auto-wires listeners by the type-hinted event class - no manual registration needed

- [x] **2.3 Mail Classes**
  - `YouAreNextMail` - Queued, markdown template
  - `OneStopAwayMail` - Queued, markdown template
  - Email templates in `resources/views/emails/`

### Phase 3: Routes & Authorization - COMPLETE

- [x] **3.1 Routes** (in `routes/web.php`)
  - Authenticated: `/dashboard`, `/runs`, `/runs/create`, `/runs/{run}`, `/runs/{run}/edit`
  - Public driver: `/driver/run/{uuid}`, `/driver/run/{uuid}/active`

- [x] **3.2 RunPolicy**
  - viewAny/create: user must have a business_id
  - view: user's business_id must match run's business_id
  - update/delete: same + run must be in Pending status

### Phase 4: Business Owner UI - COMPLETE

- [x] **4.1 Dashboard** - Stats cards (active/pending/completed today), recent runs table with status badges
- [x] **4.2 Runs Index** - Paginated table with status filter, share modal with copyable URL/PIN
- [x] **4.3 Runs Create** - Form with run name, PIN (auto-generated), dynamic delivery rows, **drag-and-drop reordering (wire:sort)**
- [x] **4.4 Runs Edit** - Pre-filled form, only accessible for pending runs (via RunPolicy), **drag-and-drop reordering (wire:sort)**
- [x] **4.5 Runs Show** - Progress tracking with polling, delivery status list, share modal

### Phase 5: Driver UI - COMPLETE

- [x] **5.1 AccessRun** - Mobile-friendly PIN entry screen using **flux:otp component**, session-based authentication
- [x] **5.2 ActiveRun** - Mobile-optimized TODO interface with Start button, large Complete buttons, progress footer, **drag-and-drop reordering (wire:sort)**

### Phase 6: Testing - COMPLETE

- [x] **6.1 Feature Tests**
  - `RunManagementTest` - CRUD, authorization
  - `DriverAccessTest` - PIN validation, session handling
  - `DeliveryNotificationTest` - Email sending, state transitions

---

## Notes & Issues Encountered

1. **Enum creation:** `php artisan make:enum Enums/DeliveryStatus` created a nested `app/Enums/Enums/` directory - had to move the file and fix namespace manually. Use just `php artisan make:enum DeliveryStatus` next time.

2. **Routes before components:** Adding Livewire routes before the component classes exist causes Laravel to error. Workaround: comment out routes, create components, then uncomment.

3. **Migrations not yet run:** The database migrations have been created but not run. You'll need to run `php artisan migrate` (or `lando artisan migrate`) to apply them.

4. **Livewire v4 beta upgrade:** Upgraded to Livewire v4 beta to try new features. Key additions used:
   - `wire:sort` directive for drag-and-drop reordering (no more manual JS!)
   - `flux:otp` component for the driver PIN entry (cleaner 2FA-style UX)

---

## Next Steps (When Resuming)

1. **Run migrations** - `php artisan migrate` to create the tables (if not already done)

2. **Seed test data** - `php artisan db:seed --class=TestDataSeeder` for local development

3. **Write feature tests** - COMPLETE

4. **Test the full flow manually**
   - Create a run as a business owner
   - Share the link with a driver
   - Enter PIN and start the run
   - Complete deliveries and verify notifications

---

## Files Created

```
app/Enums/RunStatus.php
app/Enums/DeliveryStatus.php
app/Models/Delivery.php
app/Events/RunStarted.php
app/Events/DeliveryCompleted.php
app/Listeners/SendInitialNotifications.php
app/Listeners/SendNextDeliveryNotifications.php
app/Mail/YouAreNextMail.php
app/Mail/OneStopAwayMail.php
app/Policies/RunPolicy.php
app/Livewire/Dashboard.php
app/Livewire/Runs/Index.php
app/Livewire/Runs/Create.php
app/Livewire/Runs/Edit.php
app/Livewire/Runs/Show.php
app/Livewire/Driver/AccessRun.php
app/Livewire/Driver/ActiveRun.php
resources/views/livewire/dashboard.blade.php
resources/views/livewire/runs/index.blade.php
resources/views/livewire/runs/create.blade.php
resources/views/livewire/runs/edit.blade.php
resources/views/livewire/runs/show.blade.php
resources/views/livewire/driver/access-run.blade.php
resources/views/livewire/driver/active-run.blade.php
resources/views/emails/you-are-next.blade.php
resources/views/emails/one-stop-away.blade.php
database/factories/DeliveryFactory.php
database/migrations/2025_12_06_115126_add_business_id_to_users_table.php
database/migrations/2025_12_06_115150_create_deliveries_table.php
```

## Files Modified

```
database/migrations/2025_12_06_112057_create_businesses_table.php
database/migrations/2025_12_06_112110_create_runs_table.php
app/Models/Business.php
app/Models/Run.php
app/Models/User.php
routes/web.php
database/seeders/TestDataSeeder.php
database/factories/BusinessFactory.php
database/factories/RunFactory.php
```
