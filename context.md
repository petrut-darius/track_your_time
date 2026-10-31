# Track Your Time - Project Context

## Project Summary

**Track Your Time** is a full-stack motorsport and track-day lap timing and community platform. It allows drivers, track-day enthusiasts, and automotive hobbyists to register accounts, log and manage their personal car collections (with horsepower, stories, and photo uploads), record circuit lap times across different track configurations, compete on global and friendship-based leaderboards, build a friends network, and receive live, real-time notifications when friends update their profiles or hit new milestones.

### Tech Stack Overview
- **Backend**: Symfony 7.3+ (PHP 8.4) REST API, Doctrine ORM, MariaDB 11.8, structured in `backend/`.
- **Authentication**: Stateless cookie-based JWT authentication (`LexikJWTAuthenticationBundle`) and refresh token mechanism (`GesdinetJWTRefreshTokenBundle`), with HttpOnly cookies (`BEARER` and `refresh_token`), custom user normalizer on login success, and role/voter authorization.
- **Message Broker & Asynchronous Processing**: RabbitMQ (AMQP) message broker interacting via Symfony Messenger (`php-amqp` extension) with automatic retries and dead-letter queue (`failed` transport).
- **Real-Time Push Engine**: Mercure Hub (`dunglas/mercure` running as a Docker service in DDEV) delivering private Server-Sent Events (SSE) over HTTP/2.
- **Frontend**: Angular 21 (standalone components, signals, Zoneless change detection via `provideZonelessChangeDetection()`), Tailwind CSS 4, RxJS, and rich-text WYSIWYG editing (`@domternal/angular`), structured in `frontend/`.
- **Environment & Infrastructure**: DDEV local development environment hosting the Symfony web server (Nginx-FPM), MariaDB database, Mercure container, Mailpit, and phpMyAdmin. The Angular frontend runs with Vite/Angular CLI dev server and proxies `/api` and `/.well-known/mercure` to the DDEV backend.

---

## Current State

### Backend
- **Data Models & Persistence**:
  - Complete Doctrine entities: `User`, `Car`, `Circuit`, `CircuitTime`, `Friendship`, `Notification`, `Country`, `Manufacture`, `Model`, `Grade`, `Status`, `Type`, `Configuration`, `ResetPasswordRequest`, `RefreshToken`.
  - `Notification` entity tracks `id`, `recepient` (`User`), `type` (e.g., `profile-edit`), `content`, `targetId`, and `readAt` timestamp, with clean serialization group `notifications:read` (circular reference on `recepient` removed).
- **REST Endpoints & Controllers**:
  - **Auth & Identity**: `/api/register`, `/api/login_check`, `/api/token/refresh`, `/api/logout`, `/api/me`.
  - **Cars**: `/api/cars` (index, pagination, filtering), `/api/cars/create` (dual-payload ingestion: JSON or multipart with file upload via `FileUploaderService`), `/api/cars/{id}`, `/api/cars/{id}/edit`.
  - **Circuits & Times**: `/api/circuits`, `/api/circuits/{id}`, lap times recording via `CircuitTimeController`.
  - **Friends & Social**: `/api/friends` (send request, accept, reject/remove), `/api/profile/{id}` (view profile, cars, and friends).
  - **Leaderboards**: `/api/leaderboard` (global circuit rankings) and friends-only leaderboards.
  - **Notifications** (`NotificationController`):
    - `GET /api/notifications`: Retrieves current user's notification history and unread count.
    - `GET /api/notifications/subscribe`: Issues Mercure subscriber token conforming to RFC 9068 with `aud` claims, sets `__Secure-mercure_access_token` cookie, and returns `{ hubUrl, topic, token }`.
    - `PATCH /api/notifications/{id}/read`: Marks a single notification as read.
    - `POST /api/notifications/read-all`: Batch marks all unread notifications for the user as read.
- **Live Notifications Pipeline (Backend)**:
  - `ProfileController::edit()` detects profile changes, queries accepted friends, and dispatches a `LiveNotificationMessage` onto the Symfony MessageBus (`messenger.bus.default`).
  - `backend/config/packages/messenger.yaml` routes `LiveNotificationMessage` to the `async` AMQP transport (RabbitMQ).
  - `LiveNotificationMessageHandler` consumes messages, saves a new `Notification` entity to MariaDB, and calls Symfony Mercure `HubInterface::publish()` to publish a private update (`private: true`) to topic `https://track-your-time.ddev.site/users/{id}/notifications`.
  - Mercure Hub container is configured and running in DDEV on external ports `8444` (HTTPS) and `8088` (HTTP) with matching 64-char JWT secret and CORS allowing `http://localhost:4200`.

### Frontend
- **Routing & Views**:
  - Angular standalone routes protected by `AuthGuard` and `NoAuthGuard`.
  - Implemented components: `Navbar`, `Footer`, `Home`, `Login`, `Register`, `ForgotPassword`, `ResetPassword`, `ProfileIndex`, `ProfileEdit`, `CarIndex`, `CarShow`, `CarCreate`, `CarEdit`, `CircuitsIndex`, `CircuitsShow`, `CountriesIndex`, `CountriesShow`, `Friends`, `Leaderboard`, `FriendsLeaderboard`.
  - Reusable components: `Pagination` (dynamic sliding window), `SearchInput` (debounced search with URL query synchronization).
- **Live Notifications Integration (Frontend)**:
  - `NotificationService` (`frontend/src/app/services/notification.ts`):
    - Reactive state with Angular signals: `notifications`, `unreadCount`, `isConnected`.
    - Automatically initializes on user authentication (`Auth.user$`) and disconnects on logout.
    - Connects to Mercure SSE via streaming `fetch()` with `Authorization: Bearer <token>` and `match` parameter.
    - Auto-reconnects with backoff on network/stream disconnects.
    - Prepends incoming notifications, increments unread counter, and triggers toast alerts.
  - `Navbar` (`frontend/src/app/components/navbar/`):
    - Interactive notification bell with unread badge counter.
    - Dropdown panel displaying recent notifications, unread state highlights, and "Mark all as read" button.
    - Click-outside auto-close listener (`@HostListener`).
  - `NotificationToast` (`frontend/src/app/components/notification-toast/`):
    - Globally mounted in `app.html` for real-time animated popup alerts.
    - Auto-dismisses after 6 seconds, or clicking marks the notification as read and navigates to the updater's profile.
- **State & Communication**:
  - `Auth` service manages user authentication state using RxJS `BehaviorSubject` (`user$`, `authStatus$`) initialized at app startup via `provideAppInitializer()`.
  - `errorInterceptor` intercepts HTTP errors.
  - Proxy configuration in `frontend/proxy.conf.json` forwards `/api` and `/.well-known/mercure` to the DDEV backend.

---

## In Progress

- **End-to-End Multi-User Testing**:
  - Validating real-time delivery across two active browser sessions: User A updates profile -> RabbitMQ queues message -> worker consumes -> Mercure publishes -> User B receives real-time toast and navbar badge increment live without refresh.
- **Expanding Event Notifications**:
  - Dispatching `LiveNotificationMessage` on friend requests / acceptances in `FriendController`.
  - Dispatching `LiveNotificationMessage` when circuit lap records or personal bests are beaten in `CircuitTimeController`.

---

## Decisions Made (with reasons)

1. **Asynchronous Notification Pipeline (Symfony Messenger + RabbitMQ)**:
   - *Decision*: Offload notification persistence and Mercure HTTP calls into a background worker using Symfony Messenger and RabbitMQ rather than executing them synchronously during `ProfileController::edit()`.
   - *Reason*: Preserves millisecond-level response times for user HTTP requests; prevents third-party or network latency (such as Mercure publishing) from blocking the client; provides automatic retries (up to 3 attempts with exponential backoff) and dead-letter queue fault recovery.

2. **Mercure Hub (SSE over HTTP/2) over WebSockets**:
   - *Decision*: Adopt Mercure Hub (`dunglas/mercure`) for real-time push instead of WebSocket servers (like Ratchet or Socket.IO).
   - *Reason*: Mercure is an open standard built on Server-Sent Events (SSE) and HTTP/2; provides automatic reconnects and stream resumption (`Last-Event-ID`) out of the box; eliminates the overhead of managing stateful WebSocket server infrastructure in PHP; integrates cleanly with standard HTTP tooling.

3. **Private Topic Authorization**:
   - *Decision*: Mark notification updates with `private: true` and publish to per-user topics (`https://track-your-time.ddev.site/users/{id}/notifications`).
   - *Reason*: Ensures strict data confidentiality and multi-tenant isolation. Only subscribers possessing a valid JWT authorization token with permissions for their specific topic can subscribe or receive messages.

4. **Streaming `fetch()` SSE Client Subscription**:
   - *Decision*: Use streaming `fetch()` with `ReadableStreamDefaultReader` and `Authorization: Bearer <token>` in `NotificationService` instead of native `EventSource`.
   - *Reason*: Browser `EventSource` does not support custom HTTP headers like `Authorization`. Passing bearer tokens via `fetch()` avoids third-party cookie restrictions in cross-origin local dev (`http://localhost:4200` to `https://mercure.track-your-time.ddev.site:8444`) while maintaining clean chunk decoding and `AbortController` cancellation.

5. **Dual Audience (`aud`) in Subscriber JWTs**:
   - *Decision*: Include both internal (`http://mercure...`) and external (`https://mercure.track-your-time.ddev.site:8444/.well-known/mercure`) URLs in the `aud` claim during token generation.
   - *Reason*: Satisfies Mercure Protocol 1.0 (RFC 9068) strict audience verification whether requests arrive from container-to-container routing or external host Traefik termination.

6. **HttpOnly Cookie Authentication**:
   - *Decision*: Use HttpOnly cookies (`BEARER` and `refresh_token`) rather than saving JWT tokens in browser `localStorage`.
   - *Reason*: Protects tokens from Cross-Site Scripting (XSS) extraction attacks while allowing seamless authenticated requests with `{ withCredentials: true }`.

7. **Zoneless Angular 21 Architecture & Signal State**:
   - *Decision*: Build the frontend using Angular 21 with `provideZonelessChangeDetection()` and standalone components, managing notification UI state with Angular signals (`notifications`, `unreadCount`).
   - *Reason*: Eliminates the performance overhead of `zone.js`, embraces Angular's modern signals-based reactive architecture, and guarantees instantaneous UI updates.

8. **Dual-Payload Transport for Car Creation**:
   - *Decision*: Allow `CarController::create()` to ingest either pure `application/json` (when no photos are attached) or `multipart/form-data` (when binary photos are uploaded).
   - *Reason*: Optimizes network payload size and parsing speed for photo-less creations while supporting seamless multipart uploads when images are present.

---

## Known Issues

1. **Messenger Background Worker Required**:
   - *Issue*: Dispatched notification messages remain in RabbitMQ until a worker is running.
   - *Impact*: Developers must have `ddev exec php backend/bin/console messenger:consume async` actively running in the background to process events and push updates to Mercure.

### Resolved Issues
- **`MERCURE_JWT_SECRET` Secret Mismatch**: Harmonized `.ddev/.env` to the 64-character secret matching `backend/.env.local`.
- **Mercure Protocol 1.0 Claims & Audience Requirement**: Configured `NotificationController::subscribe()` to mint tokens with required `iss`, `sub`, `client_id`, `aud`, and `Grant::ACTION_SUBSCRIBE`.
- **CORS Restrictions on Mercure for Angular Dev Server**: Added `http://localhost:4200` to `cors_origins` in `.ddev/docker-compose.mercure.yaml` and proxied `/.well-known/mercure` in `frontend/proxy.conf.json`.
- **Missing Backend Notification Endpoints**: Created `NotificationController` with endpoints for history, subscription, and read toggles.
- **Circular Entity Reference**: Removed circular `notifications:read` serialization group on `Notification::$recepient`.

---

## Next Steps

1. **End-to-End Live Verification**:
   - Run the Messenger consumer: `ddev exec php backend/bin/console messenger:consume async -vv`.
   - Perform a live profile edit with User A and confirm User B receives the live toast alert and navbar badge increment without page reload.

2. **Extend Real-Time Notifications**:
   - Dispatch `LiveNotificationMessage` when a friend request is sent or accepted in `FriendController`.
   - Dispatch `LiveNotificationMessage` when a friend records a faster lap time on a circuit in `CircuitTimeController`.

3. **Admin Dashboard Development**:
   - Continue implementing user role permissions on CRUD admin controllers.
