# gesprender-core-framework

Minimalist PHP core for projects.


# Install

1. Clone Core and Backoffice repositories
2. Create Data Base
3. Configure .env file in Core folder or Backoffice folder, anyway
4. In Core folder, execute `npm run intall`
5. Go to http://localhost

# Commands

Set up and install the project:

```
npm install
```

Activate development mode with host reload

```
npm run dev
```

Note: The Docker container has a React image installed, so the project is self-sustaining. However, on some computers, it may slow down the frontend. This command `run dev` stop the React image and start the server separately, allowing for a smoother development experience.



Drop and re-install database

```
npm run db
```

Set up and install the project with Apache server (tested on XAMPP)

```
npm run apache
```

Up server react for project (without Docker)

```
npm run apache-dev
```

Create Backoffice build

```
npm run build
```


# CoreShell CLI

Create custom module for project

```
php coreshell make:module ModuleName
```

Install database migrations or first migration database.

```
php coreshell migrations:migrate
```


# Folder structure

```php
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
└── 📁Sites
└── 📁src
    └── 📁Classes
    └── 📁Contracts
    └── 📁Cron
    └── 📁Services
    └── 📁Storage
└── 📁vendor
```
