# twinepm-server-heroku
A PHP backend for the Twine Package Manager.

## Structure

The Twine Package Manager (TwinePM) is composed of four back-end servers, which manage data logic, persistence, and an HTML utility microclient; and a front-end server, which provides a rich web abstraction of nearly all the back-end's endpoints and the primary path for user-driven browser requests.

### Front-end
There are 4 discrete layers of the front-end. All are contained within a 

1. Building - npm scripting is used to automate bundling a Node codebase through webpack, with transpiled by Babel, using an Express server and outputting a React/Redux progressive web app, server-rendered with next.js), 

### Back-end
There are 6 discrete layers of the back-end. The first two are only planned to be present in the development environment.

1. Building - Written in Python 3.7, the top-level build scripting primarily uses the argparse and subprocess modules.
2. Virtualization - Virtualizes the entire development (or, less usefully and currently unused, production) environment, using a Vagrantfile (written in Ruby) Ubuntu 16.04 image to creating a VirtualBox virtual machine through.
3. Containerization - Services in TwinePM is isolated and networked within Docker containers. This does not apply to Redis or PostgreSQL servers, which are currently slated to be hosted as Heroku add-ons. This provides file isolation, security increases, and automated building. Containerization is also planned to support clustering as the site scales, either through Docker Swarm (likely), Kubernetes (less likely), or Mesos (unlikely).
4. HTTP - nginx, contained within a Debian Stretch slim filesystem, is used to serve back-end requests. All requests (save for those for the front-end client, which are reverse-proxied to the Express server if they are sharing a server) are routed to a single file used for further logic routing.
5. Logic - A PHP-FPM server interpreting PHP 7.1, contained within a Debian Stretch slim filesystem, is used to intermediate between endpoint requests and persistent data stores. The logic layer is also responsible for authorization and maintaining information security. For requests that query PostgreSQL, the OAuth 2 implicit grant is used with JWT bearer tokens for API authentication and RSA-2048/RSA-SHA256 are used for encryption/message authentication. For requests that query Redis, AES-256-CTR/SHA-256 is used for encryption/message authentication. The PHP function random_bytes is used globally for cryptographically random request keys and salts. With the exception of the microclient endpoints, which emit HTML, every machine-readable endpoint is an idempotent REST interface using CRUD (POST, GET, PUT, and DELETE, respectively) and emits JSON. Each REST endpoint will soon possess an OPTIONS method displaying metadata about the endpoint and the service at large. All backend requests run through a single PHP file using the Slim microframework to route requests and pass a dependency injection container to Endpoint objects.
6. Persistence - Facilitated through two database servers, one for storing permanent or timed content stored on disk, and the other for caching ephemeral state in RAM. The former is a PostgreSQL server, hosted on Heroku as an addon in production and contained within a Debian Jessie filesystem in development, and the latter is a Redis server hosted on Heroku as an addon in production and contained within a Debian Jessie slim filesystem.