# DeliverEase

DeliverEase is a mobile-focused web application designed for small, local businesses that handle their own deliveries. It solves a common problem: keeping customers informed about when their delivery is arriving without requiring expensive logistics software or constant phone calls.

The app works in two parts. Business owners create "delivery runs" - essentially an ordered list of customers getting deliveries that day. They can then share a secure link with their driver. The driver uses a simple, mobile-friendly interface to mark deliveries as complete. As they progress through the route, customers automatically receive email notifications when they're next in line or one stop away.

## Features

- **Run Management** - Business owners can create, edit, and track delivery runs with drag-and-drop reordering
- **Secure Driver Access** - Share links use UUIDs with PIN protection for security
- **Automatic Notifications** - Customers receive "You're Next" and "One Stop Away" emails as the driver progresses
- **Real-time Progress Tracking** - Business owners can monitor delivery progress with live updates
- **Mobile-first Driver UI** - Large, touch-friendly controls designed for busy drivers on the go

## Tech Stack

- **Laravel 12** - PHP framework
- **Livewire v4 (beta)** - Dynamic interfaces with `wire:sort` for drag-and-drop
- **Flux UI Pro** - Component library (Tailwind CSS + Vite)
- **Lando** - Local development environment

## Getting Started

### Prerequisites

- [Lando](https://lando.dev/) installed on your machine
- Git

### Installation

1. Clone the repository:
```bash
git clone git@github.com:ohnotnow/deliver-ease.git
cd deliver-ease
```

2. Set up environment and dependencies:
```bash
cp .env.example .env
composer install
npm install
npm run build
```

3. Start Lando and set up the database:
```bash
lando start
# If this is your first run, lando start may error due to missing DB tables
lando mfs  # Migrate and seed the database
```

4. Access the application at the URL shown by `lando info` (typically https://deliver-ease.lndo.site)

### Default Login

- **Email:** `test@example.com`
- **Password:** `secret`

The seeder also creates sample delivery runs with PIN `1234` and `5678` for testing the driver interface.

### Development

- **Start Lando**: `lando start`
- **Migrate and Seed database**: `lando mfs`
- **Install dependencies**: `lando composer install` / `lando npm install`
- **Build assets**: `lando npm run build`
- **Run tests**: `lando artisan test`

### Common Lando Commands

- `lando artisan [command]` - Run Laravel artisan commands
- `lando composer [command]` - Run Composer commands
- `lando npm [command]` - Run npm commands
- `lando mysql` - Access MySQL shell
- `lando mfs` - Custom command to migrate fresh and seed (uses `TestDataSeeder`)

## How It Works

1. **Business owner** creates a new delivery run with customer emails (and optional names/addresses)
2. **Business owner** shares the run link + PIN with their driver
3. **Driver** enters PIN and clicks "Start" - first two customers receive notifications
4. **Driver** marks each delivery complete - next customer in line gets notified
5. **Business owner** can track progress in real-time from their dashboard

## License

This project is licensed under the MIT License.
