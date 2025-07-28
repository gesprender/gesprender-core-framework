# gesprender-core-framework

A minimalist PHP core framework for rapid project development.

## Overview

The framework was designed for rapid development, without the heavy caching or processes that frameworks like Symfony or Laravel bring by default. We provide an integrated Backend and Frontend (Backoffice) so that the developer only needs to focus on developing the required functionality and integrating it into the client's website.

A key feature is the "Multi-Tenant Mode," which allows the code to be deployed once and used by multiple websites (multiple clients). The framework automatically recognizes the requesting domain and loads the corresponding database configuration. See more details in the `.env` file configuration.

## Architecture and Patterns

The framework is built following a modular and clean architecture, implementing several design patterns:

### Architecture

- **Modular Architecture**: The framework is organized into independent modules that encapsulate specific functionality.
- **Multi-Tenant**: Native support for multiple clients/domains with separate databases.
- **Centralized Kernel**: A central core (`Kernel.php`) that handles application bootstrap, configuration loading, and routing.

### Design Patterns

- **Singleton Pattern**: Implemented in the Kernel to ensure a single application instance.
- **Factory Pattern**: Used in service and response creation.
- **Repository Pattern**: Implemented in the data access layer.
- **Service Layer Pattern**: Clear separation of business logic in services.
- **Middleware Pattern**: Middleware system for HTTP request processing.

### Directory Structure

```
src/
├── Classes/      # Base and utility classes
├── Contracts/    # Interfaces and contracts
├── Services/     # Application services
├── Storage/      # Storage and persistence
└── Cron/         # Scheduled tasks
```

### Execution Flow

1. Kernel initializes the application
2. Loads environment configuration (.env)
3. Initializes session
4. Loads controllers and endpoints
5. Handles request routing
6. Processes response

## Installation

1. Clone Core and Backoffice repositories
2. Create Database
3. In Core folder, execute `npm run install`
4. Configure .env file
5. Go to http://localhost
6. Add permissions in `chmod -R 755 /var/www/html/Backoffice/src/Modules` in PHP image container

## Available Commands

Set up and install the project:

```bash
npm install
```

Activate development mode with host reload:

```bash
npm run dev
```

Note: The Docker container has a React image installed, making the project self-sustaining. However, on some computers, it may slow down the frontend. The `run dev` command stops the React image and starts the server separately, allowing for a smoother development experience.

Drop and re-install database:

```bash
npm run db
```

Set up and install the project with Apache server (tested on XAMPP):

```bash
npm run apache
```

Start React server for project (without Docker):

```bash
npm run apache-dev
```

Create Backoffice build:

```bash
npm run build
```

## CoreShell CLI

Create custom module for project (includes inter-module communication by default):

```bash
php coreshell make:module ModuleName
```

Install database migrations or first migration database:

```bash
php coreshell migrations:migrate
```

## Documentation

Comprehensive documentation is available in the `/Docs` folder:

### 📚 Core Documentation
- **[Framework Documentation](Docs/framework-documentation.md)** - Complete framework overview, architecture, and best practices
- **[Database Guide](Docs/database-guide.md)** - 🆕 **Connection and queries with the new system**

### 🔧 Development & Modules  
- **[Developer Module Extension Guide](Docs/developer-module-extension-guide.md)** - Step-by-step guide for creating and extending modules
- **[Module Communication Architecture](Docs/module-communication-architecture.md)** - Inter-module communication system design
- **[CLI Integration](Docs/cli-integration.md)** - CoreShell CLI with integrated communication system

### 🆕 Recent Updates
- ✅ **Fixed "Cannot read properties of null (reading 'toLowerCase')"** error in CoreHooks
- ✅ **New database system** with Dependency Injection and Repository Pattern  
- ✅ **Updated documentation** with practical examples and best practices

### Key Features

- **🔄 Inter-Module Communication**: Event-driven architecture for decoupled module interaction
- **🎯 Auto-Discovery**: Automatic module detection and loading
- **🛠️ CLI Integration**: Generate modules with communication capabilities by default
- **📡 Event System**: Dispatch and listen to events across modules
- **🔧 Service Registry**: Share services between modules
- **🎨 Hooks System**: WordPress-like extensibility with actions and filters

## Project Structure

```
└── 📁.vscode
    └── launch.json
    └── settings.json
    └── tasks.json
└── 📁api
    └── index.php
└── 📁Backoffice
└── 📁config
    └── alias.php
    └── defines.php
    └── Kernel.php
└── 📁Docker
    └── Dockerfile
    └── nginx.conf
    └── php.ini
└── 📁Logs
    └── errors.log
└── 📁src
    └── 📁Classes
    └── 📁Contracts
    └── 📁Cron
    └── 📁Services
    └── 📁Storage
└── 📁vendor
```
