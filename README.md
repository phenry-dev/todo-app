# Todo App - Technical Explanation Guide

## Overview
This is a full-stack todo application built with **Laravel 12** (backend) and **Nuxt 3** (frontend), implementing a RESTful API architecture with token-based authentication.

---

## 1. BACKEND ARCHITECTURE (Laravel)

### 1.1 Technology Stack
- **Framework**: Laravel 12 (PHP 8.2+)
- **Database**: SQLite (for development)
- **Authentication**: Laravel Sanctum (token-based API authentication)
- **Testing**: Pest PHP
- **Architecture Pattern**: Repository Pattern + Service Layer

### 1.2 Project Structure

```
backend/
├── app/
│   ├── Http/
│   │   ├── Controllers/Api/     # API controllers
│   │   ├── Requests/            # Form request validation
│   │   └── Resources/           # API resource transformers
│   ├── Interfaces/              # Repository interfaces
│   ├── Models/                  # Eloquent models
│   ├── Policies/                # Authorization policies
│   ├── Providers/               # Service providers
│   └── Repositories/            # Repository implementations
├── database/
│   ├── migrations/              # Database schema
│   └── seeders/                 # Database seeders
├── routes/
│   └── api.php                  # API routes
└── tests/                       # Feature & unit tests
```

### 1.3 Key Design Patterns

#### Repository Pattern
**Why?** Separates data access logic from business logic, making the code more testable and maintainable.

**Implementation:**
- `TaskRepositoryInterface` - Defines the contract
- `TaskRepository` - Implements data access methods
- Injected into `TaskController` via dependency injection

**Benefits:**
- Easy to swap database implementations
- Better testability (can mock repositories)
- Centralized data access logic

#### Service Layer (Controllers)
Controllers act as thin service layers that:
- Handle HTTP requests/responses
- Delegate business logic to repositories
- Use Form Requests for validation
- Return API Resources for consistent JSON responses

### 1.4 Database Schema

**Users Table:**
- `id`, `email`, `password`, `timestamps`

**Tasks Table:**
- `id` - Primary key
- `user_id` - Foreign key (cascade delete)
- `statement` - Task description (text)
- `is_completed` - Boolean flag
- `due_date` - Date field
- `order` - Integer for manual ordering
- `timestamps` - created_at, updated_at

**Key Features:**
- Foreign key constraint ensures data integrity
- Cascade delete: when user is deleted, their tasks are deleted
- `order` field enables drag-and-drop reordering

### 1.5 API Endpoints

#### Authentication Routes (`routes/api.php`)
```php
POST /api/login          # Public - Authenticate user
POST /api/logout         # Protected - Revoke token
GET  /api/user           # Protected - Get current user
```

#### Task Routes (Protected with `auth:sanctum`)
```php
GET    /api/tasks              # List tasks (with date filter & search)
POST   /api/tasks              # Create new task
PUT    /api/tasks/{id}         # Update task
DELETE /api/tasks/{id}         # Delete task
POST   /api/tasks/reorder      # Reorder tasks (drag & drop)
```

**Query Parameters:**
- `date` - Filter tasks by due date (YYYY-MM-DD)
- `q` - Search tasks by statement (optional)

### 1.6 Authentication Flow (Laravel Sanctum)

1. **Login Process:**
   - User sends email/password to `/api/login`
   - `AuthController::login()` validates credentials
   - Creates a personal access token via `$user->createToken()`
   - Returns user data + token

2. **Token Usage:**
   - Frontend stores token in localStorage
   - Sends token in `Authorization: Bearer {token}` header
   - Laravel Sanctum middleware validates token
   - `auth()->id()` gets authenticated user ID

3. **Logout:**
   - Deletes the current access token
   - Token becomes invalid immediately

### 1.7 Request Validation

**Form Request Classes:**
- `LoginRequest` - Validates email/password
- `StoreTaskRequest` - Validates task creation (statement, due_date)
- `UpdateTaskRequest` - Validates task updates
- `ReorderTasksRequest` - Validates reorder payload

**Benefits:**
- Centralized validation logic
- Automatic error responses
- Reusable validation rules

### 1.8 API Resources

**TaskResource** transforms Eloquent models to JSON:
- Maps `statement` → frontend expects `title`
- Maps `is_completed` → frontend expects `done`
- Formats dates consistently
- Ensures API contract stability

### 1.9 Authorization (Policies)

**TaskPolicy** ensures:
- Users can only access their own tasks
- Applied via `$this->authorize()` in controller
- Prevents unauthorized access

### 1.10 Task Ordering Logic

**Manual Ordering:**
- Each task has an `order` field (integer)
- New tasks get `max(order) + 1` for that date
- Drag-and-drop sends new order array
- `reorderTasks()` updates orders in a transaction

**Why Transactions?**
- Ensures all-or-nothing updates
- Prevents inconsistent state if one update fails

---

## 2. FRONTEND ARCHITECTURE (Nuxt 3)

### 2.1 Technology Stack
- **Framework**: Nuxt 3 (Vue 3)
- **State Management**: Pinia
- **Styling**: Tailwind CSS
- **Icons**: Lucide Vue Next
- **Type Safety**: TypeScript
- **HTTP Client**: Nuxt's `$fetch` (built-in)

### 2.2 Project Structure

```
frontend/
├── components/
│   ├── DateSidebar.vue      # Date navigation sidebar
│   ├── TaskForm.vue          # Task input form
│   ├── TaskList.vue          # Task list container
│   ├── TaskItem.vue          # Individual task item
│   └── ui/                   # Reusable UI components
├── composables/
│   └── useApi.ts             # API client composable
├── pages/
│   ├── login.vue             # Login page
│   └── index.vue             # Main tasks page
├── stores/
│   ├── auth.ts               # Authentication store
│   └── task.ts               # Task management store
└── types/
    └── index.ts               # TypeScript type definitions
```

### 2.3 State Management (Pinia)

#### Auth Store (`stores/auth.ts`)
**State:**
- `user` - Current user object
- `token` - Authentication token
- `loading` - Loading state
- `error` - Error messages

**Methods:**
- `login()` - Authenticate user
- `logout()` - Clear auth state
- `loadFromStorage()` - Restore from localStorage
- `persist()` - Save to localStorage

**Persistence:**
- Token stored in localStorage
- Automatically loaded on app start
- Survives page refreshes

#### Task Store (`stores/task.ts`)
**State:**
- `tasks` - Array of all tasks
- `loading` - Loading state
- `error` - Error messages

**Computed Properties:**
- `tasksByDate(date)` - Filter tasks by date
- `searchTasks(query, date)` - Search functionality

**Methods:**
- `fetchTasks()` - Load tasks from API
- `addTask()` - Create new task
- `updateTask()` - Update existing task
- `toggleDone()` - Toggle completion
- `deleteTask()` - Remove task
- `reorderTasks()` - Update task order

**Data Transformation:**
- Maps backend `statement` → frontend `title`
- Maps backend `is_completed` → frontend `done`
- Extracts date from ISO string

### 2.4 API Client (`composables/useApi.ts`)

**Centralized HTTP Client:**
- Automatically adds `Authorization` header from auth store
- Handles Laravel response unwrapping
- Consistent error handling
- Type-safe with TypeScript generics

**Methods:**
- `get<T>()` - GET requests
- `post<T>()` - POST requests
- `put<T>()` - PUT requests
- `delete()` - DELETE requests

**Response Unwrapping:**
- Laravel returns `{ data: {...} }`
- `unwrapResponse()` extracts the `data` property
- Frontend receives clean data structure

### 2.5 Component Architecture

#### Page Components

**`login.vue`:**
- Form with email/password
- Calls `authStore.login()`
- Redirects to `/` on success
- Shows error messages

**`index.vue`:**
- Main application page
- Protected route (redirects if not authenticated)
- Manages selected date
- Handles search query
- Coordinates task operations

#### Feature Components

**`DateSidebar.vue`:**
- Displays date navigation (Today, Yesterday, dates)
- Week separators ("Last week", "3rd Week of July")
- Highlights selected date
- Emits date selection events

**`TaskForm.vue`:**
- Textarea for task input
- Submit button (circular with arrow icon)
- Dynamic placeholder based on task count
- Emits submit event with task title

**`TaskList.vue`:**
- Renders list of tasks
- Handles delete confirmation overlay
- Manages drag-and-drop events
- Shows loading/empty states

**`TaskItem.vue`:**
- Individual task display
- Checkbox for completion
- Inline editing
- Delete button
- Drag-and-drop support

### 2.6 Data Flow

#### Task Creation Flow:
1. User types in `TaskForm`
2. Form emits `submit` event with title
3. `index.vue` calls `taskStore.addTask(title, date)`
4. Store calls `api.post('/api/tasks', {...})`
5. Backend creates task, returns response
6. Store adds task to local state
7. UI updates automatically (reactive)

#### Task Update Flow:
1. User clicks checkbox or edits task
2. `TaskItem` emits `toggle` or `edit` event
3. `index.vue` calls `taskStore.toggleDone()` or `updateTask()`
4. Store makes API call
5. Updates local state optimistically
6. UI reflects changes immediately

#### Task Reordering Flow:
1. User drags task item
2. `TaskItem` emits `dragstart` with task ID
3. User drops on target
4. `TaskList` emits `drop` with target ID
5. `index.vue` calculates new order
6. Calls `taskStore.reorderTasks(date, ids[])`
7. Backend updates all orders in transaction
8. Frontend updates local order values

### 2.7 Routing & Navigation

**Nuxt File-Based Routing:**
- `pages/login.vue` → `/login`
- `pages/index.vue` → `/`

**Route Protection:**
- `index.vue` checks `auth.token` on mount
- Redirects to `/login` if not authenticated
- Uses `useRouter()` for navigation

### 2.8 UI/UX Features

**Design:**
- Blurred mountain background (gradient)
- White card layout with rounded corners
- Minimalist, clean interface
- Responsive design

**Interactions:**
- Drag-and-drop task reordering
- Inline task editing
- Real-time search
- Date-based navigation
- Optimistic UI updates

**Error Handling:**
- Error messages displayed in UI
- Loading states during API calls
- Graceful error recovery

---

## 3. BACKEND-FRONTEND INTEGRATION

### 3.1 API Communication

**Base URL Configuration:**
- Defined in `nuxt.config.ts` as `apiBase`
- Points to Laravel backend (e.g., `http://localhost:8000`)

**Request Flow:**
1. Frontend makes request via `useApi()` composable
2. Adds `Authorization: Bearer {token}` header
3. Laravel Sanctum validates token
4. Controller processes request
5. Returns JSON response
6. Frontend transforms response to match UI needs

### 3.2 Data Mapping

**Backend → Frontend:**
- `statement` → `title`
- `is_completed` → `done`
- `due_date` (ISO) → `date` (YYYY-MM-DD)
- `order` → `order` (unchanged)

**Why Mapping?**
- Frontend uses more intuitive property names
- Consistent data structure across frontend
- Backend can change without breaking frontend

### 3.3 Error Handling

**Backend:**
- Validation errors return 422 with error messages
- Authentication errors return 401
- Not found returns 404
- Server errors return 500

**Frontend:**
- Catches errors in try/catch blocks
- Displays user-friendly messages
- Stores errors in Pinia stores
- Shows error UI components

### 3.4 Laravel Sanctum:

**Laravel Sanctum:**
- Configured in `config/sanctum.php`
- Allows frontend origin
- Handles preflight requests
- Supports credentials (cookies/tokens)

---

## 4. KEY FEATURES IMPLEMENTATION

### 4.1 Date-Based Task Organization

**Backend:**
- Tasks filtered by `due_date` field
- Query parameter: `?date=2025-01-15`
- Supports date range queries

**Frontend:**
- Date picker in sidebar
- Shows "Today", "Yesterday", formatted dates
- Week separators for organization
- Automatically fetches tasks when date changes

### 4.2 Search Functionality

**Backend:**
- Optional `q` query parameter
- SQL `LIKE` query on `statement` field
- Case-insensitive search

**Frontend:**
- Search input in header
- Real-time filtering (computed property)
- Searches across all tasks or filtered by date

### 4.3 Drag-and-Drop Reordering

**Implementation:**
- HTML5 Drag and Drop API
- Frontend calculates new order
- Sends array of `{id, order}` to backend
- Backend updates in transaction
- Frontend updates optimistically

**Why This Approach?**
- Immediate UI feedback
- Transaction ensures data consistency
- Handles concurrent updates safely

### 4.4 Optimistic Updates

**Strategy:**
- Update UI immediately
- Make API call in background
- Revert on error

**Example (Toggle Task):**
1. User clicks checkbox
2. UI updates immediately (optimistic)
3. API call made
4. On error: revert UI change

**Benefits:**
- Perceived performance improvement
- Better user experience
- Handles errors gracefully

---

## 5. TESTING

### 5.1 Backend Tests (Pest PHP)

**Feature Tests:**
- `AuthenticationTest.php` - Login/logout flows
- `TaskApitTest.php` - CRUD operations, reordering

**Test Coverage:**
- Authentication endpoints
- Task CRUD operations
- Authorization (users can't access others' tasks)
- Validation rules
- Error handling

### 5.2 Testing Strategy

**Unit Tests:**
- Repository methods
- Model relationships
- Business logic

**Feature Tests:**
- API endpoints
- Authentication flows
- Authorization policies

---

## 6. SECURITY CONSIDERATIONS

### 6.1 Authentication
- Token-based (Laravel Sanctum)
- Tokens stored securely
- Automatic token validation
- Token revocation on logout

### 6.2 Authorization
- Policy-based authorization
- Users can only access their own tasks
- Enforced at controller level

### 6.3 Data Validation
- Form Request validation
- SQL injection prevention (Eloquent ORM)
- XSS prevention (Vue auto-escaping)
- CSRF protection (Sanctum)

### 6.4 Password Security
- Bcrypt hashing
- Never stored in plain text
- Secure password comparison

---

## 7. DEPLOYMENT CONSIDERATIONS

### 7.1 Database
- Mysql for development
- Migrations ensure schema consistency

### 7.3 Process
- Frontend: `pnpm dev` 
- Backend: `composer install` (dependencies)

---

## 8. TALKING POINTS FOR ASSESSOR

### Architecture Decisions:
1. **Repository Pattern**: "I chose the repository pattern to separate data access from business logic, making the code more testable and maintainable."

2. **Pinia for State**: "I used Pinia instead of Vuex because it's the recommended state management for Vue 3, with better TypeScript support and a simpler API."

3. **Laravel Sanctum**: "I chose Sanctum over Passport because it's lighter weight for SPA authentication, and tokens are simpler to manage than OAuth."

4. **TypeScript**: "I added TypeScript to catch errors at compile time and improve developer experience with autocomplete and type safety."

5. **Optimistic Updates**: "I implemented optimistic UI updates to improve perceived performance, with error handling to revert changes if the API call fails."

6. **Component Architecture**: "I organized components by feature (TaskForm, TaskList, TaskItem) rather than by type, making the codebase more maintainable."

7. **API Resources**: "I used Laravel API Resources to transform data consistently and maintain a stable API contract even if the database schema changes."

### Code Quality:
- **Separation of Concerns**: Clear separation between UI, state management, and API calls
- **Reusability**: Composables and components are reusable
- **Type Safety**: TypeScript ensures type safety across the application
- **Error Handling**: Comprehensive error handling at all levels
- **Testing**: Feature tests ensure API endpoints work correctly

### Performance:
- **Optimistic Updates**: Immediate UI feedback
- **Computed Properties**: Efficient reactive filtering/searching
- **Lazy Loading**: Components loaded as needed
- **Efficient Queries**: Backend queries are optimized with proper indexing

---

