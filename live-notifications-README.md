# 🔔 Live Notifications Architecture & Implementation Guide (`Track Your Time`)

Comprehensive technical documentation for the asynchronous real-time notification pipeline in the **Track Your Time** application: **Symfony Backend** (`ProfileController`) ➔ **RabbitMQ Message Broker** (via Symfony Messenger) ➔ **Mercure Hub** (Server-Sent Events) ➔ **Client Subscribers**.

---

## 📑 Table of Contents

1. [Architectural Overview](#-architectural-overview)
2. [End-to-End System Workflow](#-end-to-end-system-workflow)
3. [Component Breakdown](#-component-breakdown)
   - [1. Event Producer: Profile Controller](#1-event-producer-profilecontroller)
   - [2. Message DTO: `LiveNotificationMessage`](#2-message-dto-livenotificationmessage)
   - [3. Queue & Broker: RabbitMQ (Symfony Messenger AMQP)](#3-queue--broker-rabbitmq-symfony-messenger-amqp)
   - [4. Consumer & Persistence: `LiveNotificationMessageHandler`](#4-consumer--persistence-livenotificationmessagehandler)
   - [5. Database Entity: `Notification`](#5-database-entity-notification)
   - [6. Real-Time Push Engine: Mercure Hub](#6-real-time-push-engine-mercure-hub)
   - [7. Frontend Subscriber Integration](#7-frontend-subscriber-integration)
4. [Configuration Reference](#-configuration-reference)
   - [Symfony Messenger Configuration](#symfony-messenger-configuration)
   - [Symfony Mercure Configuration](#symfony-mercure-configuration)
   - [DDEV & Docker Services](#ddev--docker-services)
   - [Environment Variables](#environment-variables)
5. [Operational & Debugging Guide](#-operational--debugging-guide)
   - [Running the Messenger Consumer](#running-the-messenger-consumer)
   - [Inspecting Queues & Failed Messages](#inspecting-queues--failed-messages)
   - [Verifying Mercure SSE Stream](#verifying-mercure-sse-stream)
6. [Best Practices & Security Notes](#-best-practices--security-notes)

---

## 🏗 Architectural Overview

The live notifications system provides real-time event dissemination while ensuring that web HTTP requests remain ultra-fast and resilient. Rather than synchronously writing notifications to the database and making blocking HTTP requests to Mercure during a user action, the application leverages an **Event-Driven, Asynchronous Pipeline**:

```mermaid
flowchart TD
    subgraph ClientLayer["Frontend / Browser"]
        U1["User A (Updater)"]
        U2["User B (Friend / Recipient)"]
    end

    subgraph AppLayer["Symfony Backend (Web Server)"]
        PC["ProfileController::edit()<br/>(PATCH /api/profile)"]
        BUS["Symfony MessageBus<br/>(messenger.bus.default)"]
    end

    subgraph BrokerLayer["Message Broker (RabbitMQ)"]
        RMQ["RabbitMQ Exchange & Queue<br/>(track_your_time)"]
    end

    subgraph WorkerLayer["Symfony Messenger Worker"]
        WORKER["messenger:consume async"]
        HANDLER["LiveNotificationMessageHandler"]
        DB[("MariaDB / MySQL<br/>notification table")]
    end

    subgraph RealTimeLayer["Real-Time Gateway"]
        MERCURE["Mercure Hub (Docker / SSE)<br/>dunglas/mercure"]
    end

    U1 -->|"1. Submits profile edit"| PC
    PC -->|"2. Detects changes & iterates accepted friends"| BUS
    BUS -->|"3. Dispatches LiveNotificationMessage"| RMQ
    PC -->>|"4. Returns 200 OK immediately"| U1

    RMQ -->|"5. Consumes async message"| WORKER
    WORKER -->|"6. Invokes handler"| HANDLER
    HANDLER -->|"7. Persists notification record"| DB
    HANDLER -->|"8. Publishes private update"| MERCURE

    MERCURE -->|"9. Pushes SSE Event to active stream"| U2
```

### Why This Architecture?

| Concern | Solution |
| :--- | :--- |
| **Response Latency** | Profile updates respond in milliseconds. Database inserts for notifications and network hops to Mercure are offloaded to background workers. |
| **Fault Isolation** | If Mercure Hub is temporarily restarting, or if sending a notification fails, the user's profile update still succeeds. |
| **Reliability & Retries** | Symfony Messenger automatically retries transient failures (3 attempts with exponential backoff) and moves unrecoverable messages to a dead-letter queue (`failed` transport). |
| **Security & Privacy** | Mercure updates are flagged as `private: true`, targeted specifically to topic URIs containing recipient IDs (`https://track-your-time.ddev.site/users/{id}/notifications`). |

---

## 🔄 End-to-End System Workflow

```mermaid
sequenceDiagram
    autonumber
    actor UserA as User A (Actor)
    participant Controller as ProfileController
    participant Bus as MessageBusInterface
    participant RabbitMQ as RabbitMQ (AMQP)
    participant Worker as Messenger Consumer
    participant Handler as LiveNotificationMessageHandler
    participant Database as MySQL Database
    participant Mercure as Mercure Hub
    actor UserB as User B (Friend)

    UserB->>Mercure: Connect to SSE stream (/users/UserB_ID/notifications)
    activate Mercure
    Note over UserB, Mercure: EventSource connection established

    UserA->>Controller: PATCH /api/profile (Updated username, email, etc.)
    activate Controller
    Controller->>Database: Update User A & flush changes
    Controller->>Database: Query accepted friendships for User A
    Database-->>Controller: List of Friendships (including User B)

    loop For each accepted friend
        Controller->>Bus: dispatch(LiveNotificationMessage)
        Bus->>RabbitMQ: Publish AMQP message to queue
    end

    Controller-->>UserA: 200 OK (Updated user profile data)
    deactivate Controller

    Note over RabbitMQ, Worker: Asynchronous background processing
    RabbitMQ->>Worker: Dequeue LiveNotificationMessage
    activate Worker
    Worker->>Handler: __invoke(LiveNotificationMessage)
    activate Handler

    Handler->>Database: Find recipient entity (User B)
    Handler->>Database: Persist new Notification entity & flush
    Database-->>Handler: Saved with generated ID

    Handler->>Mercure: POST update to Hub (Topic: /users/UserB_ID/notifications, Private: true)
    activate Mercure
    Mercure-->>Handler: 200 OK (Published)
    deactivate Handler
    Worker-->>RabbitMQ: ACK message
    deactivate Worker

    Mercure-->>UserB: SSE Push: notification payload JSON
    deactivate Mercure
    Note over UserB: Frontend updates notification badge & shows toast
```

---

## 🧩 Component Breakdown

### 1. Event Producer: ProfileController
- **File**: [`backend/src/Controller/ProfileController.php`](file:///opt/lampp/htdocs/track_your_time/backend/src/Controller/ProfileController.php)
- **Method**: `edit()`
- **Endpoint**: `PATCH /api/profile`

When an authenticated user updates their profile fields (`email`, `username`, `firstName`, or `lastName`), the controller:
1. Validates the incoming payload against [`ProfileUpdateDTO`](file:///opt/lampp/htdocs/track_your_time/backend/src/DTO/ProfileUpdateDTO.php).
2. Persists the modified fields on the `User` entity and flushes the database transaction.
3. Retrieves all accepted friends of the user using [`FriendshipRepository`](file:///opt/lampp/htdocs/track_your_time/backend/src/Repository/FriendshipRepository.php):
   ```php
   $friendships = $this->em->getRepository(Friendship::class)->createQueryBuilder('f')
       ->andWhere("f.status = :status")
       ->andWhere("f.user = :user OR f.friend = :user")
       ->setParameter("status", "accepted")
       ->setParameter("user", $user)
       ->getQuery()
       ->getResult();
   ```
4. For each friend, resolves their unique ID and dispatches a [`LiveNotificationMessage`](file:///opt/lampp/htdocs/track_your_time/backend/src/Message/LiveNotificationMessage.php):
   ```php
   foreach ($friendships as $friend) {
       $friendId = $friend->getOtherUser($user)->getId();

       $this->bus->dispatch(new LiveNotificationMessage(
           "profile-edit",
           $user->getId(),
           $user->getUsername() . " has updated their profile!",
           $friendId,
       ));
   }
   ```
5. Returns a `200 OK` JSON response containing the updated user. The user's browser does not wait for notifications to be sent.

---

### 2. Message DTO: `LiveNotificationMessage`
- **File**: [`backend/src/Message/LiveNotificationMessage.php`](file:///opt/lampp/htdocs/track_your_time/backend/src/Message/LiveNotificationMessage.php)
- **Attribute**: `#[AsMessage('async')]`

Represents an immutable message payload passed through the message bus:

```php
namespace App\Message;

use Symfony\Component\Messenger\Attribute\AsMessage;

#[AsMessage('async')]
final class LiveNotificationMessage
{
    public function __construct(
        private string $type,        // Event identifier (e.g., "profile-edit")
        private int $targetId,       // Entity ID that triggered the notification (e.g., updater's user ID)
        private string $content,     // Human-readable message text
        private int $recepientId,    // Destination User ID
    ) {}

    public function getType(): string { return $this->type; }
    public function getTargetId(): int { return $this->targetId; }
    public function getContent(): string { return $this->content; }
    public function getRecepientId(): int { return $this->recepientId; }
}
```

---

### 3. Queue & Broker: RabbitMQ (Symfony Messenger AMQP)
- **Config**: [`backend/config/packages/messenger.yaml`](file:///opt/lampp/htdocs/track_your_time/backend/config/packages/messenger.yaml)
- **Environment**: `MESSENGER_TRANSPORT_DSN` in [`backend/.env.local`](file:///opt/lampp/htdocs/track_your_time/backend/.env.local)

The message is routed asynchronously:
```yaml
framework:
    messenger:
        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                retry_strategy:
                    max_retries: 3
                    multiplier: 2
            failed: 'doctrine://default?queue_name=failed'

        routing:
            'App\Message\LiveNotificationMessage': async
```

- When `MessageBusInterface->dispatch()` is invoked, Symfony serializes the object and sends an AMQP message to the RabbitMQ broker via the PHP AMQP extension (`php-amqp`).
- If an unhandled exception occurs inside the consumer handler, the retry strategy will re-queue the message up to 3 times with exponential backoff before sending it to the `failed` Doctrine transport.

---

### 4. Consumer & Persistence: `LiveNotificationMessageHandler`
- **File**: [`backend/src/MessageHandler/LiveNotificationMessageHandler.php`](file:///opt/lampp/htdocs/track_your_time/backend/src/MessageHandler/LiveNotificationMessageHandler.php)
- **Attribute**: `#[AsMessageHandler]`

The handler is invoked by `messenger:consume async`:

```php
namespace App\MessageHandler;

use App\Entity\Notification;
use App\Message\LiveNotificationMessage;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

#[AsMessageHandler]
final class LiveNotificationMessageHandler
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $em,
        private HubInterface $hub
    ) {}

    public function __invoke(LiveNotificationMessage $message): void
    {
        $recepient = $this->userRepository->findOneBy(["id" => $message->getRecepientId()]);

        // 1. Create and persist the Notification record for offline history
        $notification = new Notification();
        $notification->setContent($message->getContent());
        $notification->setRecepient($recepient);
        $notification->setType($message->getType());
        $notification->setTargetId($message->getTargetId());

        $this->em->persist($notification);
        $this->em->flush();

        // 2. Format a private Mercure topic unique to the recipient
        $topic = sprintf(
            'https://track-your-time.ddev.site/users/%d/notifications',
            $recepient->getId()
        );

        // 3. Prepare serialized JSON payload
        $payload = json_encode([
            'id' => $notification->getId(),
            'type' => $notification->getType(),
            'content' => $notification->getContent(),
            'targetId' => $notification->getTargetId(),
            'readAt' => null,
        ], JSON_THROW_ON_ERROR);

        // 4. Publish private real-time update to Mercure Hub
        $update = new Update(
            $topic,
            $payload,
            true, // Private update (requires subscriber JWT token with topic permission)
        );

        $this->hub->publish($update);
    }
}
```

---

### 5. Database Entity: `Notification`
- **Entity**: [`backend/src/Entity/Notification.php`](file:///opt/lampp/htdocs/track_your_time/backend/src/Entity/Notification.php)
- **Repository**: [`backend/src/Repository/NotificationRepository.php`](file:///opt/lampp/htdocs/track_your_time/backend/src/Repository/NotificationRepository.php)
- **Database Table**: `notification`

| Field | Type | Description |
| :--- | :--- | :--- |
| `id` | `INT AUTO_INCREMENT` | Primary Key. |
| `recepient_id` | `INT NOT NULL` | Foreign key referencing `user(id)`. |
| `type` | `VARCHAR(20) NOT NULL` | Categorizes the event (`profile-edit`, etc.). |
| `content` | `VARCHAR(50) NOT NULL` | The notification text displayed to the user. |
| `target_id` | `INT NULL` | ID of the target resource (e.g., ID of the user who edited profile). |
| `read_at` | `DATETIME NULL` | Nullable timestamp populated when the user reads the notification. |

---

### 6. Real-Time Push Engine: Mercure Hub
- **Service Container**: `.ddev/docker-compose.mercure.yaml`
- **Hub Package Config**: [`backend/config/packages/mercure.yaml`](file:///opt/lampp/htdocs/track_your_time/backend/config/packages/mercure.yaml)

Mercure is an open protocol and high-performance server (written in Go / Caddy) for pushing Server-Sent Events (SSE) to web browsers and mobile apps.

#### Why Mercure over WebSockets?
- Built directly on **HTTP/2 & HTTP/3** and **Server-Sent Events (SSE)**.
- Re-connection, stream resumption (`Last-Event-ID`), and multiplexing work out-of-the-box.
- Native JWT authentication for private topic authorization.
- Completely decoupled from the Symfony application server.

---

### 7. Frontend Subscriber Integration

To receive notifications in real time, client applications (e.g. Angular frontend) subscribe to Mercure using the standard HTML5 `EventSource` API.

#### Topic Convention
```text
https://track-your-time.ddev.site/users/{currentUserId}/notifications
```

#### TypeScript / Angular Implementation Example
```typescript
import { Injectable, NgZone } from '@angular/core';
import { Observable, Subject } from 'rxjs';

export interface LiveNotification {
  id: number;
  type: string;
  content: string;
  targetId: number;
  readAt: string | null;
}

@Injectable({
  providedIn: 'root'
})
export class NotificationService {
  private notificationsSubject = new Subject<LiveNotification>();
  public notifications$ = this.notificationsSubject.asObservable();
  private eventSource: EventSource | null = null;

  constructor(private zone: NgZone) {}

  public subscribeToNotifications(userId: number, mercureHubUrl: string): void {
    const topic = `https://track-your-time.ddev.site/users/${userId}/notifications`;
    const url = new URL(mercureHubUrl);
    url.searchParams.append('topic', topic);

    // withCredentials: true sends the Mercure authorization cookie if configured
    this.eventSource = new EventSource(url.toString(), { withCredentials: true });

    this.eventSource.onmessage = (event: MessageEvent) => {
      this.zone.run(() => {
        const notification: LiveNotification = JSON.parse(event.data);
        this.notificationsSubject.next(notification);
      });
    };

    this.eventSource.onerror = (error) => {
      console.error('Mercure connection error:', error);
    };
  }

  public disconnect(): void {
    if (this.eventSource) {
      this.eventSource.close();
      this.eventSource = null;
    }
  }
}
```

---

## ⚙️ Configuration Reference

### Symfony Messenger Configuration
[`backend/config/packages/messenger.yaml`](file:///opt/lampp/htdocs/track_your_time/backend/config/packages/messenger.yaml):

```yaml
framework:
    messenger:
        failure_transport: failed

        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
                retry_strategy:
                    max_retries: 3
                    multiplier: 2
            failed: 'doctrine://default?queue_name=failed'

        default_bus: messenger.bus.default

        routing:
            'App\Message\LiveNotificationMessage': async
```

### Symfony Mercure Configuration
[`backend/config/packages/mercure.yaml`](file:///opt/lampp/htdocs/track_your_time/backend/config/packages/mercure.yaml):

```yaml
mercure:
    hubs:
        default:
            url: '%env(default::MERCURE_URL)%'
            public_url: '%env(default::MERCURE_PUBLIC_URL)%'
            jwt:
                secret: '%env(MERCURE_JWT_SECRET)%'
                publish: '*'
                claims:
                    iss: '%env(MERCURE_JWT_ISSUER)%'
                    sub: 'app'
                    client_id: 'app'
```

### DDEV & Docker Services
- **DDEV Extra Package**: `webimage_extra_packages: ['php${DDEV_PHP_VERSION}-amqp']` in [`.ddev/config.yaml`](file:///opt/lampp/htdocs/track_your_time/.ddev/config.yaml#L188) installs the AMQP extension for RabbitMQ support inside the container.
- **Mercure Container**: [`.ddev/docker-compose.mercure.yaml`](file:///opt/lampp/htdocs/track_your_time/.ddev/docker-compose.mercure.yaml):
  - Image: `dunglas/mercure`
  - Internal Service Name: `mercure` (Port `80`)
  - External Virtual Host: `mercure.track-your-time.ddev.site` (Port `8444` HTTPS / `8088` HTTP)
  - Publisher & Subscriber JWT Key: `${MERCURE_JWT_SECRET}`

### Environment Variables
[`backend/.env.local`](file:///opt/lampp/htdocs/track_your_time/backend/.env.local):

```env
# RabbitMQ AMQP Transport
MESSENGER_TRANSPORT_DSN=amqp://tracK_app:30Ianpdi!@host.docker.internal:5672/track_your_time

# Mercure Configuration
MERCURE_URL=http://mercure/.well-known/mercure
MERCURE_PUBLIC_URL=https://mercure.track-your-time.ddev.site:8444/.well-known/mercure
MERCURE_JWT_SECRET=d32de31645ca3807e60b6a53a50282a735e9e725659b57f45be3a640d29b68b4
MERCURE_JWT_ISSUER=app
```

---

## 🛠 Operational & Debugging Guide

### Running the Messenger Consumer

To process queued notification messages, run the Symfony Messenger worker:

```bash
# Inside DDEV container:
ddev exec php bin/console messenger:consume async -vv

# Or with time and memory limits for production daemon managers (e.g. Supervisor, systemd):
ddev exec php bin/console messenger:consume async --time-limit=3600 --memory-limit=128M
```

> [!TIP]
> In development, add `-vv` to observe each dequeued message and execution trace in real time.

### Inspecting Queues & Failed Messages

```bash
# Inspect queued messages count across transports
ddev exec php bin/console messenger:stats

# View messages that failed processing
ddev exec php bin/console messenger:failed:show

# Retry all failed messages
ddev exec php bin/console messenger:failed:retry

# Remove a specific failed message by ID
ddev exec php bin/console messenger:failed:remove <id>
```

### Verifying Mercure SSE Stream

You can test subscription to Mercure directly from your terminal using `curl`:

```bash
# Subscribe to the topic with a subscriber authorization JWT
curl -N -H "Authorization: Bearer <SUBSCRIBER_JWT>" \
  "https://mercure.track-your-time.ddev.site:8444/.well-known/mercure?topic=https://track-your-time.ddev.site/users/1/notifications"
```

---

## 🔒 Best Practices & Security Notes

1. **Private Topics**:
   The third argument in `new Update($topic, $payload, true)` flags the topic as private. Mercure t any subscriber that does not present a valid JWT authorizing access to that topic or wildcard pattern.
2. **Strict Recipient Filtering**:
   Only friends with an `"accepted"` status receive profile update notifications, ensuring pending or rejected friendship relations are not notified.
3. **Database Audit & Persistence**:
   Notifications are saved in MariaDB prior to publishing to Mercure. If a user is offline when an update happens, they can still view their notification history when they next log in.
4. **Decoupled Architecture**:
   The Messenger bus architecture allows future events (e.g., circuit lap record beaten, friendship request received) to dispatch `LiveNotificationMessage` with different `type` parameters without modifying the transport or consumer pipeline.
