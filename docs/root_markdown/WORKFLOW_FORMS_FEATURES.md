# Workflow Forms - Complete Feature Set

## Overview
All workflow task forms (Create and Edit) now include comprehensive features for managing tasks including team member assignment with a user picker interface.

## Form Fields

### 1. **Task Title** ✓
- Required field
- Text input up to 255 characters
- Used for task identification

### 2. **Description** ✓
- Required field
- Textarea for detailed task information
- Supports multi-line input

### 3. **Priority** ✓
- Required field
- Dropdown selector with options: Low, Medium, High
- Default: Medium

### 4. **Status** ✓
- Optional field (defaults to "New" / "pending" on creation)
- Dropdown selector with options:
  - New (pending)
  - In Progress
  - Testing
  - Awaiting Feedback
  - Completed
  - Delayed

### 5. **Assign To (NEW)** ✓
- Searchable team member picker
- Features:
  - Displays list of active team members
  - Shows user avatar/initials
  - Searchable by name
  - Dropdown with checkmark for selected user
  - Clear button to unassign
  - Stores user ID for backend processing
- Returns `assigned_to` field to API with user ID

### 6. **Tracking Number** ✓
- Optional field
- Text input for shipment/parcel tracking reference
- Up to 255 characters

### 7. **Due Date** ✓
- Optional field
- DateTime picker
- Can set specific date and time
- Stored as `due_at` in backend

### 8. **Tags** ✓
- Optional field
- Add multiple tags to categorize tasks
- Tag management:
  - Add tags with "+" button or Enter key
  - Remove individual tags with "x" button
  - Display as removable badges

## Data Transformation

The forms use a data transformation layer that converts form data to API format:

| Form Field | API Field |
|-----------|-----------|
| `assignedTo` | `assigned_to` |
| `trackingNumber` | `tracking_number` |
| `dueDate` | `due_at` |

This transformation happens in `hooks/useWorkflowQueue.ts` in the `transformWorkflowData()` function.

## Components

### New Components Added
- **UserSelect** (`components/ui/UserSelect.tsx`): 
  - Reusable component for selecting team members
  - Supports avatar display
  - Searchable interface
  - TypeScript typed with `UserOption` interface

### Updated Components
- **CreateWorkflowModal** (`components/workflow/CreateWorkflowModal.tsx`):
  - Added `assignableUsers` prop
  - Replaced text input with `UserSelect` component for "Assign To" field

- **EditWorkflowModal** (`components/workflow/EditWorkflowModal.tsx`):
  - Added `assignableUsers` prop
  - Replaced text input with `UserSelect` component for "Assign To" field

- **WorkflowBoard** (`pages/operations/WorkflowBoard.tsx`):
  - Passes `assignableUsers` array to both modals
  - Fetches active team members from API endpoint

## API Integration

### Backend Endpoints Used
- **Create Task**: `POST /v10/workflow-items`
  - Accepts `assigned_to` field (user ID)
  
- **Update Task**: `PUT /v10/workflow-items/{id}`
  - Accepts `assigned_to` field for reassignment
  
- **Assign Task**: `PATCH /v10/workflow-items/{id}/assign`
  - Dedicated endpoint for assignment

### Backend Models
- **WorkflowTask** Model:
  - Has `assigned_to` column (nullable, references `users.id`)
  - Has `assignee()` relationship to User model
  - Automatically validates user exists via `exists:users,id` rule

- **WorkflowItemResource**:
  - Returns both `assigned_user_id` and `assigned_user_name`
  - Includes user avatar if available

## API Response Fields

Tasks are returned with the following assignment-related fields:
```json
{
  "assigned_user_id": "123",           // User ID
  "assigned_user_name": "John Doe",    // User name for display
  "assigned_user_avatar": "https://..." // User avatar URL
}
```

## Validation Rules

### Backend Validation (Laravel)
- `assigned_to`: nullable, must exist in users table if provided
- `title`: required, max 255 characters
- `description`: required
- `priority`: required, must be one of: low, medium, high
- `status`: must be one of: pending, in_progress, testing, awaiting_feedback, completed, delayed
- `due_at`: nullable, must be valid date format
- `tracking_number`: nullable, max 255 characters
- `tags`: nullable array of strings

## Form State Management

Both modals manage state using React hooks:
- `formData`: Current form state
- `tagInput`: Temporary input for adding tags
- Tags are stored as array and can be added/removed dynamically

## Success Flow

1. User opens Create/Edit Modal
2. System fetches list of active team members
3. User fills in form fields including selecting team member
4. Form data is transformed (camelCase → snake_case)
5. API request sent with correct field names
6. Backend validates and stores assignment
7. UI updates with new task/changes
8. Workflow board refreshes to show updates

## Testing the Feature

### Manual Testing Steps:
1. Navigate to Workflow Board
2. Click "Create Task"
3. Fill in all required fields
4. Click "Assign To" dropdown to select a team member
5. Type to search for team member name
6. Click to select
7. Submit form
8. Verify task appears with assigned user
9. Edit task to reassign or unassign user

### Expected Behavior:
- Dropdown shows only active users (status: 1)
- Search filters users in real-time
- Selected user shows avatar and name
- Clear button removes assignment
- Assignment persists across page reloads
- API receives correct `assigned_to` user ID
