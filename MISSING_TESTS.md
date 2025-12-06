# Missing or Future Tests

- Authentication & policy guards: enforce that unauthenticated users are redirected from runs/driver routes; users without a `business_id` are denied `viewAny/create`; `RunPolicy` denies viewing/updating/deleting for users without matching business; updating completed runs should be forbidden explicitly.
- Run creation UX: regeneration/generation of PIN yields 4 digits; removing deliveries never allows zero items; wire:sort reorder persists positions on save.
- Run update/delete edge cases: deleting via index/edit for in-progress runs returns forbidden (index path currently untested); updating a completed run remains blocked; deletion cascades in-progress/started runs should stay blocked.
- Driver access: invalid UUID returns 404; cannot bypass session check to start/complete; entering PIN with extra whitespace/non-numeric still rejected; session cleared or run completed should redirect back to access.
- Driver actions: calling `startRun` when already in progress/completed should not requeue notifications or change timestamps; `completeDelivery` should no-op/forbid when delivery is pending or already completed, or when the delivery belongs to another run.
- Events/timestamps: `Run::start` sets `started_at`; `Run::markCompleted` sets `completed_at`; deliveries set `notified_at`/`completed_at` when status changes; listeners dispatch expected events without duplicates.
- Mail content: `YouAreNextMail` and `OneStopAwayMail` render expected variables (businessName, recipientName) and queue counts stay correct for varying list sizes (e.g., single-delivery run).
- Models/helpers: `Delivery::display_name` falls back to email; scopes `pending/notified/completed` behave; `Run::getDriverUrl` uses named route; `currentDelivery/nextPendingDelivery/completedDeliveriesCount` cover empty and mixed states.
- Dashboard component: counts for active/pending/completed today and recent runs reflect the authenticated business only.
- Routes/views: driver active view honors completed runs (shows completed message), and show view progress percentage matches completed count.
