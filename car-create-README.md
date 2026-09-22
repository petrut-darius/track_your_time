# 🚗 Car Creation Feature Documentation (`Track Your Time`)

Technical specification and architectural guide detailing the complete **Request-to-Response lifecycle** for the Car Creation (`car-create`) feature in Track Your Time.

---

## 📑 Table of Contents
1. [Overview & Purpose](#-overview--purpose)
2. [End-to-End Request & Response Flow](#-end-to-end-request--response-flow)
3. [Frontend Architecture (Angular)](#-frontend-architecture-angular)
   - [Component: `CarCreate`](#component-carcreate)
   - [Rich Text Validation](#rich-text-validation)
   - [Service: `Car`](#service-car)
   - [Data Models](#data-models)
4. [Backend Architecture (Symfony)](#-backend-architecture-symfony)
   - [Controller: `CarController::create`](#controller-carcontrollercreate)
   - [Dual Request Ingestion Strategy](#dual-request-ingestion-strategy)
   - [Data Transfer Object: `CarDTO`](#data-transfer-object-cardto)
   - [File Upload Service: `FileUploaderService`](#file-upload-service-fileuploaderservice)
   - [Entity Persistence: `Car`](#entity-persistence-car)
5. [API Specification: `POST /api/cars/create`](#-api-specification-post-apicarscreate)
   - [Headers & Authentication](#headers--authentication)
   - [Request Body A: JSON (No Photos)](#request-body-a-json-no-photos)
   - [Request Body B: Multipart / Form-Data (With Photos)](#request-body-b-multipart--form-data-with-photos)
   - [Response: 201 Created](#response-201-created)
   - [Response: 422 Unprocessable Entity (Validation Errors)](#response-422-unprocessable-entity-validation-errors)
   - [Response: 401 Unauthorized](#response-401-unauthorized)
6. [Field Validation Rules Matrix](#-field-validation-rules-matrix)
7. [Debugging & Architectural Pitfalls](#-debugging--architectural-pitfalls)

---

## 🎯 Overview & Purpose

The **Car Creation** feature allows an authenticated user to add a new car to their profile by providing:
- **Name**: Short identifier/title for the car (e.g. `"BMW E30 M3"`).
- **Horsepower (HP)**: Positive numerical engine power.
- **Story**: An HTML-formatted narrative managed via a rich-text WYSIWYG editor (`@domternal/angular`).
- **Photos (Optional)**: One or multiple binary image files uploaded to the server and linked to the car entity.

The architecture employs a **dual-payload transport pattern**:
- **Without photos**: Transmitted as a compact, fast `application/json` payload.
- **With photos**: Dynamically packed into a `multipart/form-data` payload containing both binary file streams and standard form fields.

---

## 🔄 End-to-End Request & Response Flow

```mermaid
sequenceDiagram
    autonumber
    actor User as User / Browser
    participant Comp as CarCreate Component
    participant Svc as Car Service (Angular)
    participant Http as HttpClient / Browser Engine
    participant Sym as Symfony Router / Firewall
    participant Ctrl as CarController::create
    participant Ser as Symfony Serializer / Request
    participant Val as Symfony Validator
    participant Disk as FileUploaderService
    participant DB as MariaDB / Doctrine

    User->>Comp: Fills form (name, hp, story) & selects photos
    User->>Comp: Clicks "Add car."
    
    Comp->>Comp: Client-side validation (Required, minlength, richTextLength)
    alt Client form is invalid
        Comp-->>User: Highlights invalid inputs & disables submit
    else Client form is valid
        Comp->>Svc: createCar(payload)
        
        alt Has Photos (FileList / File[])
            Svc->>Svc: Convert to FormData with append("photos[]", file)
        else No Photos
            Svc->>Svc: Construct plain JS object { name, hp, story }
        end

        Svc->>Http: POST /api/cars/create (withCredentials: true)
        Http->>Sym: HTTP POST /api/cars/create + JWT / Session Cookie
        
        Sym->>Sym: Authenticate User via #[IsGranted("IS_AUTHENTICATED_FULLY")]
        alt Not Authenticated
            Sym-->>Http: 401 Unauthorized
            Http-->>Svc: HttpErrorResponse (status 401)
            Svc-->>Comp: Error callback
            Comp-->>User: "Your session expired. Please log in again."
        else Authenticated
            Sym->>Ctrl: create(Request $request, #[CurrentUser] User $user)
            
            alt Request has NO files ($request->files->count() === 0)
                Ctrl->>Ser: deserialize($request->getContent(), CarDTO::class, "json")
                Ser-->>Ctrl: Hydrated $dto
            else Request HAS files ($request->files->count() > 0)
                Ctrl->>Ctrl: Extract $request->request (name, hp, story) & $request->files (photos)
            end

            Ctrl->>Val: validate($dto, null, ["car:create"])
            
            alt Validation Violations Exist
                Val-->>Ctrl: ConstraintViolationList
                Ctrl-->>Http: 422 Unprocessable Entity {"data": { "field": "message" }}
                Http-->>Svc: HttpErrorResponse (status 422)
                Svc-->>Comp: Error callback
                Comp->>Comp: applyServerValidationErrors() (maps snake_case to form controls)
                Comp-->>User: Displays inline error messages under respective inputs
            else Validation Passed
                Val-->>Ctrl: No violations
                opt Photos Present in DTO
                    Ctrl->>Disk: upload(UploadedFile $file)
                    Disk-->>Ctrl: Generated file names
                end
                Ctrl->>DB: Instantiate Car, set properties, set User, persist() & flush()
                DB-->>Ctrl: Saved Entity with generated ID
                Ctrl-->>Http: 201 Created {"data": Car}
                Http-->>Svc: 201 Created response
                Svc-->>Comp: CarResponse
                Comp->>User: Navigate to /cars/:id
            end
        end
    end
```

---

## 💻 Frontend Architecture (Angular)

### Component: `CarCreate`
- **File**: [`frontend/src/app/components/car-create/car-create.ts`](file:///opt/lampp/htdocs/track_your_time/frontend/src/app/components/car-create/car-create.ts)
- **Template**: [`frontend/src/app/components/car-create/car-create.html`](file:///opt/lampp/htdocs/track_your_time/frontend/src/app/components/car-create/car-create.html)
- **Style**: [`frontend/src/app/components/car-create/car-create.css`](file:///opt/lampp/htdocs/track_your_time/frontend/src/app/components/car-create/car-create.css)

#### 1. Form Initialization
The form manages three core fields:
```typescript
carForm: FormGroup = this.formBuilder.group({
  name: ["", [Validators.required, Validators.minLength(4)]],
  hp: [null as number | null, [Validators.required]],
  story: ["", [this.richTextLength(50, 2000)]]
});
```

#### 2. Rich Text Validation
The story field receives HTML output from the Domternal rich-text editor (`<p><strong>...</strong></p>`). Plain character counts on HTML would count markup tags towards the length requirement. The custom validator strips tags using the browser's native `DOMParser`:

```typescript
richTextLength(min: number, max: number): ValidatorFn {
  return (control: AbstractControl): ValidationErrors | null => {
    const html = control.value;
    if (typeof html !== 'string') return { required: true };

    const text = (new DOMParser().parseFromString(html, 'text/html').body.textContent ?? '').trim();

    if (text.length === 0) return { required: true };
    if (text.length < min) return { minlength: { requiredLength: min, actualLength: text.length } };
    if (text.length > max) return { maxlength: { requiredLength: max, actualLength: text.length } };
    return null;
  };
}
```

#### 3. File Input Capture
When a user selects files through `<input type="file" multiple (change)="onPhotosSelected($event)">`:
```typescript
onPhotosSelected(event: Event) {
  const input = event.target as HTMLInputElement;
  this.carPhotos = input.files ? Array.from(input.files) : [];
}
```

#### 4. Server Error Mapping (`422 Unprocessable Entity`)
When Symfony rejects the submission with validation errors:
1. Backend sends violation keys in `snake_case` (e.g. `first_name` or `hp`).
2. `toCamelCase()` transforms the key to match Angular form control names.
3. `control.setErrors({ server: message })` attaches the error directly to the Reactive Form control.
4. The template displays `<span class="error">{{ carForm.get('field')?.errors?.['server'] }}</span>`.

---

### Service: `Car`
- **File**: [`frontend/src/app/services/car.ts`](file:///opt/lampp/htdocs/track_your_time/frontend/src/app/services/car.ts)

Responsible for inspecting whether files exist and choosing the transport encoding:

```typescript
@Injectable({ providedIn: 'root' })
export class Car {
  private http = inject(HttpClient);

  createCar(credentials: CarRequest): Observable<CarResponse> {
    const hasPhotos = this.hasFiles(credentials.photos);

    const body: FormData | Omit<CarRequest, "photos"> = hasPhotos
      ? this.toFormData(credentials)
      : {
        name: credentials.name,
        story: credentials.story,
        hp: credentials.hp
      };

    return this.http.post<{data: CarResponse}>("/api/cars/create", body, { withCredentials: true }).pipe(
      map((response) => response.data),
      catchError((error) => throwError(() => error))
    );
  }

  private hasFiles(photos?: File[] | FileList): boolean {
    if (!photos) return false;
    const list = photos instanceof FileList ? Array.from(photos) : photos;
    return Array.isArray(list) && list.length > 0 && list.every(f => f instanceof File);
  }

  private toFormData(credentials: CarRequest): FormData {
    const formData = new FormData();
    if (credentials.name !== undefined) formData.append("name", credentials.name);
    if (credentials.story !== undefined) formData.append("story", credentials.story);
    if (credentials.hp !== undefined) formData.append("hp", String(credentials.hp));

    const photos = credentials.photos instanceof FileList 
      ? Array.from(credentials.photos) 
      : (credentials.photos ?? []);

    photos.forEach(file => formData.append("photos[]", file, file.name));
    return formData;
  }
}
```

---

### Data Models

#### `CarRequest`
- **File**: [`frontend/src/app/models/car-request.ts`](file:///opt/lampp/htdocs/track_your_time/frontend/src/app/models/car-request.ts)
```typescript
export interface CarRequest {
  name: string;
  hp: number;
  story: string;
  photos?: File[] | FileList;
}
```

#### `CarResponse`
- **File**: [`frontend/src/app/models/car-response.ts`](file:///opt/lampp/htdocs/track_your_time/frontend/src/app/models/car-response.ts)
```typescript
export interface CarResponse {
  id: number;
  name: string;
  hp: number;
  story: string;
  photos: string[] | null;
}
```

---

## ⚙️ Backend Architecture (Symfony)

### Controller: `CarController::create`
- **File**: [`backend/src/Controller/CarController.php`](file:///opt/lampp/htdocs/track_your_time/backend/src/Controller/CarController.php)
- **Route**: `POST /api/cars/create`
- **Security**: `#[IsGranted("IS_AUTHENTICATED_FULLY")]`

```php
#[Route("/api/cars/create", name: "app_api_car_create", methods: ["POST"])]
#[IsGranted("IS_AUTHENTICATED_FULLY")]
public function create(Request $request, #[CurrentUser] User $user): Response
```

### Dual Request Ingestion Strategy

Symfony inspects `$request->files->count()` to identify the incoming format:

```php
$violations = new ConstraintViolationList();
$dto = new CarDTO();

if ($request->files->count() === 0) {
    // 1. JSON Payload: Deserialize directly into the DTO instance
    try {
        $this->serializer->deserialize(
            $request->getContent(), 
            CarDTO::class, 
            "json", 
            [
                AbstractNormalizer::OBJECT_TO_POPULATE => $dto, 
                DenormalizerInterface::COLLECT_DENORMALIZATION_ERRORS => true, 
                AbstractNormalizer::IGNORED_ATTRIBUTES => ["id", "user"]
            ]
        );
    } catch (PartialDenormalizationException $e) {
        foreach ($e->getNotNormalizableValueErrors() as $err) {
            $violations->add(new ValidatorConstraintViolation(
                sprintf('The type must be one of "%s" (%s given)', implode(', ', $err->getExpectedTypes()), $err->getCurrentType()),
                '', [], null, $err->getPath(), null
            ));
        }
    }
} else {
    // 2. Multipart Form-Data: Read from Request ParameterBag & FileBag
    $dto->name = trim((string) $request->request->get("name"));
    $dto->hp = (int) $request->request->get("hp");
    $dto->story = trim((string) $request->request->get("story"));
    $dto->photos = $request->files->all("photos");
}

// 3. Validate DTO against the validation group
$violations->addAll($this->validator->validate($dto, null, ["car:create"]));
```

---

### Data Transfer Object: `CarDTO`
- **File**: [`backend/src/DTO/CarDTO.php`](file:///opt/lampp/htdocs/track_your_time/backend/src/DTO/CarDTO.php)

Validates incoming data before any entity operations take place:

```php
namespace App\DTO;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

class CarDTO
{
    #[Assert\NotBlank(groups: ["car:create"])]
    #[Assert\Length(min: 4, max: 50, groups: ["car:create"])]
    #[Groups(["car:create"])]
    public ?string $name = null;

    #[Assert\NotBlank(groups: ["car:create"])]
    #[Assert\Length(min: 50, max: 2000, groups: ["car:create"])]
    #[Groups(["car:create"])]
    public ?string $story = null;

    #[Assert\NotBlank(groups: ["car:create"])]
    #[Assert\Positive(groups: ["car:create"])]
    #[Groups(["car:create"])]
    public ?int $hp = null;

    public ?array $photos = null;
}
```

---

### File Upload Service: `FileUploaderService`
- **Service Registration**: Autowired with parameter `#[Autowire(service: "App\Service\FileUploaderService.cars")]`
- For every valid `UploadedFile` in `$dto->photos`, `upload($file)`:
  1. Sanitizes the original filename.
  2. Appends a unique hash (`uniqid()`).
  3. Moves the file to the configured cars directory (`car_photos_directory`).
  4. Returns the stored filename string.

---

### Entity Persistence: `Car`
- **File**: [`backend/src/Entity/Car.php`](file:///opt/lampp/htdocs/track_your_time/backend/src/Entity/Car.php)

Once validated and files uploaded:
1. `new Car()` is instantiated.
2. Fields (`name`, `hp`, `story`, `photos`) are assigned.
3. Authenticated user is linked: `$car->setUser($user)`.
4. Stored via `$this->em->persist($car); $this->em->flush();`.
5. Returns JSON with status `201 Created`.

---

## 📡 API Specification: `POST /api/cars/create`

### Headers & Authentication
- **URL**: `/api/cars/create`
- **Method**: `POST`
- **Authentication**: Required (`IS_AUTHENTICATED_FULLY`) via JWT Cookie / Bearer Token (`withCredentials: true`).

---

### Request Body A: JSON (No Photos)
- **Header**: `Content-Type: application/json`

```json
{
  "name": "Auditza",
  "hp": 468,
  "story": "<p><strong>The story of Auditza</strong><br>I bought the car when I started the 12th grade.</p>"
}
```

---

### Request Body B: Multipart / Form-Data (With Photos)
- **Header**: `Content-Type: multipart/form-data; boundary=----WebKitFormBoundary...`

| Field Key | Type | Description |
| :--- | :--- | :--- |
| `name` | `string` | Car name (min 4, max 50 chars) |
| `hp` | `string` (numeric) | Engine horsepower (positive integer) |
| `story` | `string` | Rich text HTML story (50 to 2000 chars) |
| `photos[]` | `binary` (repeatable) | Binary image file(s) |

---

### Response: 201 Created
Returned when car is successfully created and persisted to the database:

```json
{
  "data": {
    "id": 14,
    "name": "Auditza",
    "story": "<p><strong>The story of Auditza</strong><br>I bought the car when I started the 12th grade.</p>",
    "hp": 468,
    "photos": [
      "auditza-front-66f07a82.webp",
      "auditza-side-66f07a83.webp"
    ]
  }
}
```

---

### Response: 422 Unprocessable Entity (Validation Errors)
Returned when one or more constraints fail:

```json
{
  "data": {
    "name": "This value should not be blank.",
    "story": "This value should not be blank.",
    "hp": "This value should not be blank."
  }
}
```

---

### Response: 401 Unauthorized
Returned when user authentication has expired or token is missing:

```json
{
  "code": 401,
  "message": "JWT Token not found"
}
```

---

## 📊 Field Validation Rules Matrix

| Field | Client Rule (Angular) | Server Rule (`CarDTO` / Symfony) | Error Message |
| :--- | :--- | :--- | :--- |
| **`name`** | `Validators.required`<br>`Validators.minLength(4)` | `#[Assert\NotBlank]`<br>`#[Assert\Length(min: 4, max: 50)]` | `"This value should not be blank."`<br>`"name should be 4 characters or longer."` |
| **`hp`** | `Validators.required` | `#[Assert\NotBlank]`<br>`#[Assert\Positive]` | `"This value should not be blank."`<br>`"Hp should be a number"` |
| **`story`** | `richTextLength(50, 2000)` (strips HTML tags) | `#[Assert\NotBlank]`<br>`#[Assert\Length(min: 50, max: 2000)]` | `"This value should not be blank."`<br>`"Story must be between 50 and 2000 characters."` |
| **`photos`** | Optional | Optional array of `UploadedFile` | Validated by file uploader service |

---

## 🛠 Debugging & Architectural Pitfalls

### 1. Wrapping the payload in `{body}` inside `http.post`
- **Symptom**: Symfony returns `422 Unprocessable Entity` with all fields marked *"This value should not be blank."* even when filled in.
- **Cause**: Writing `http.post('/url', {body})` instead of `http.post('/url', body)`.
- **Why it breaks**: In TypeScript, `{body}` creates `{ "body": { name, story, hp } }`. When Symfony's serializer populates `CarDTO`, it searches for a top-level property called `$body`. Finding none, `$dto->name`, `$dto->story`, and `$dto->hp` remain `null`, failing validation.

### 2. FileList vs. Array in `hasFiles()`
- **Symptom**: Attaching photos still triggers a pure JSON request, dropping photos completely.
- **Cause**: An `<input type="file">` returns a DOM `FileList`. Calling `Array.isArray(FileList)` returns `false`.
- **Solution**: Always normalize using `Array.from(input.files)` or accept `FileList | File[]` with `Array.from()` inside helper methods.

### 3. Wrapping `FormData` in an object literal
- **Symptom**: Empty multipart request or `Content-Type: application/json` with `{}` payload.
- **Cause**: Passing `{ body }` when `body` is a `FormData` instance causes `HttpClient` to treat it as a standard object and run `JSON.stringify()`.
- **Solution**: Pass the `FormData` instance directly as the body argument: `this.http.post(url, body)`.

### 4. Symfony Array File Retrieval
- **Recommendation**: In Symfony 6+, use `$request->files->all("photos")` to read file arrays submitted as `photos[]` rather than `$request->files->get("photos")`.
