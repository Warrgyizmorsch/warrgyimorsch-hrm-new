# Master Development Prompt — HRMS Communication & Collaboration Module

You are a **Senior Enterprise SaaS Architect, Laravel/Livewire Developer, UI/UX Architect, and DevOps Engineer**.

We have an existing **Laravel + Livewire HRMS/ERP application** running on an internal LAN. The HRMS already contains core employee-management and productivity features.

## 1. Existing HRMS — DO NOT DUPLICATE

The following modules/features already exist and should continue working as they are:

* Employee Profiles
* Company Profiles
* Dashboard
* Check-in / Check-out
* Break-in / Break-out
* Attendance
* Location Tracking
* Tasks
* Leave Management
* Travel Requests
* Travel Claims
* General Claims
* Payroll
* Attendance Correction Requests
* Upcoming Events
* Employee Profile Editing
* Password Change / Reset
* Ticket Support
* Suggestions
* Announcements
* Quick Notes

Do **not** rebuild these modules from scratch.

Instead, integrate the new Communication & Collaboration layer with the existing HRMS.

---

# 2. Main Objective

Develop a **Microsoft Teams-inspired internal communication and collaboration platform** inside our existing HRMS.

The system should become a centralized employee workspace where employees can:

* Communicate with colleagues
* Create department/team conversations
* Share files
* Manage internal documents
* Schedule meetings
* View company calendar
* Create personal/team events
* Collaborate on tasks
* Search messages and files
* Receive notifications
* Access everything from one dashboard

The application must remain **self-hosted on the internal LAN**.

Current environment:

```text
Laravel + Livewire
Existing HRMS
Internal LAN
Existing MySQL database
Existing authentication
Existing RBAC/permissions
```

---

# 3. Core New Modules

Build the following modules.

## A. Internal Communication / Chat

Create a modern real-time communication system.

### Features

* One-to-one chat
* Group chat
* Department chat
* Team/project chat
* Conversation search
* Message search
* Reply to message
* Message reactions
* @mentions
* Message editing
* Message deletion
* Pin message
* Important/starred messages
* Forward message
* Copy message
* Attach files
* Images
* Documents
* Links
* Voice/video meeting integration-ready architecture
* Read/unread status
* Online/offline status
* Last seen
* Typing indicator
* Message timestamps
* Notification count
* Unread conversation count

Design the architecture so WebSockets/realtime communication can be added cleanly.

---

# 4. Channels / Teams

Create a Microsoft Teams-style structure.

Example:

```text
Company
│
├── Management
├── HR
├── IT
├── Development
├── Sales
├── Marketing
├── Finance
├── Operations
└── Projects
```

Each team/channel should support:

* Members
* Owners
* Admins
* Messages
* Files
* Announcements
* Calendar/events
* Shared documents
* Pinned content

Support:

* Public channels
* Private channels
* Department channels
* Project channels

Use existing HRMS employee records instead of creating duplicate users.

---

# 5. File Management

Develop a centralized internal file-management system.

Features:

* Upload files
* Multiple file upload
* Drag & drop upload
* Folder creation
* Nested folders
* Rename
* Move
* Copy
* Delete
* Download
* Preview
* File search
* File type filtering
* File size information
* Upload date
* Uploaded by
* Version history
* File replacement
* Restore previous version
* Favorites
* Recently accessed
* Shared with me
* Shared by me
* Department files
* Project files
* Company documents

Supported common formats:

```text
PDF
DOC
DOCX
XLS
XLSX
PPT
PPTX
CSV
TXT
JPG
JPEG
PNG
WEBP
ZIP
```

Implement proper access control.

Employees should only access files they are authorized to access.

---

# 6. Document Permissions

Create granular permissions.

Example:

```text
Company
 └── HR
      ├── Policies
      ├── Employee Documents
      └── Confidential
```

Permissions should support:

* View
* Download
* Upload
* Edit
* Delete
* Share
* Manage permissions

Confidential HR/management documents must never be visible to unauthorized employees.

Use the existing HRMS RBAC system wherever possible.

---

# 7. Internal Calendar

Build a centralized company calendar.

Features:

* Month view
* Week view
* Day view
* Agenda view
* Create event
* Edit event
* Delete event
* Recurring events
* Meeting scheduling
* Employee invitations
* Department events
* Project events
* Company holidays
* Birthdays
* Work anniversaries
* Leave integration
* Attendance-related events
* Task deadlines
* Reminders
* Notifications

Calendar should integrate with existing HRMS data.

Example:

```text
Calendar
│
├── Company Events
├── Meetings
├── Holidays
├── Employee Birthdays
├── Work Anniversaries
├── Leave
├── Task Deadlines
└── Project Events
```

Do not create duplicate employee/leave/event data if the existing HRMS already provides it.

---

# 8. Meetings

Create an internal meeting-management system.

Features:

* Schedule meeting
* Meeting title
* Description
* Organizer
* Participants
* Department/team
* Date/time
* Recurring meeting
* Agenda
* Attach documents
* Meeting notes
* Action items
* Meeting status
* Reminder
* Meeting history

Architecture should remain integration-ready for:

* Microsoft Teams
* Google Meet
* Zoom
* Jitsi
* Internal WebRTC

Do not hard-code any external meeting provider.

---

# 9. Announcements Enhancement

The existing announcement module should be upgraded rather than duplicated.

Support:

* Company announcements
* Department announcements
* Team announcements
* Priority announcements
* Scheduled announcements
* Attachments
* Read/unread tracking
* Acknowledgement required
* Employee acknowledgement history
* Announcement expiry
* Announcement search

Example:

```text
🔴 Critical
🟠 Important
🔵 General
🟢 Information
```

---

# 10. Suggestions Enhancement

Enhance the existing suggestion module.

Features:

* Employee suggestions
* Anonymous suggestion option
* Category
* Priority
* Admin review
* Status
* Comments
* Internal discussion
* Voting/upvoting
* Resolution
* Employee notification

Statuses:

```text
Submitted
Under Review
Accepted
Rejected
Implemented
Closed
```

---

# 11. Ticket Support Enhancement

Use the existing ticket-support module and connect it with communication.

Features:

* Ticket conversation
* Attachments
* Internal comments
* Employee comments
* Department assignment
* Priority
* SLA-ready architecture
* Status
* Ticket notifications
* Ticket activity history

Example:

```text
Open
Assigned
In Progress
Waiting for Employee
Resolved
Closed
```

---

# 12. Quick Notes

Enhance the existing Quick Notes feature.

Support:

* Personal notes
* Team notes
* Shared notes
* Rich text
* Checklists
* Attachments
* Pin notes
* Archive
* Search
* Tags
* Color/category
* Reminder
* Convert note → task

---

# 13. Notifications

Create a centralized notification center.

Notifications should cover:

* New message
* Mention
* Announcement
* File shared
* Calendar invitation
* Meeting reminder
* Task assignment
* Ticket update
* Suggestion update
* Leave status
* Attendance request
* System notification

Support:

```text
Unread
Read
Mark all as read
Notification preferences
```

---

# 14. Global Search

Build a powerful global search.

Search across:

```text
Employees
Messages
Channels
Files
Announcements
Tasks
Tickets
Suggestions
Calendar events
Notes
```

Use permission-aware search.

An employee must never receive search results for data they do not have access to.

---

# 15. Dashboard / Collaboration Workspace

Create a modern enterprise dashboard.

The dashboard should include:

```text
------------------------------------------------
Welcome, Employee
------------------------------------------------

Unread Messages       Upcoming Meetings
Pending Tasks         Announcements

------------------------------------------------

Recent Conversations

------------------------------------------------

Upcoming Calendar

------------------------------------------------

Recently Shared Files

------------------------------------------------

My Tasks

------------------------------------------------

Company Announcements

------------------------------------------------
```

Use a clean modern SaaS design inspired by:

* Microsoft Teams
* Slack
* Notion
* Zoho
* Odoo

Do NOT copy their UI exactly.

Create an original enterprise design.

---

# 16. Sidebar Navigation

Create a modern navigation structure:

```text
Dashboard

Communication
 ├── Chat
 ├── Teams
 ├── Channels
 └── Mentions

Work
 ├── Tasks
 ├── Tickets
 └── Suggestions

Files
 ├── My Files
 ├── Shared With Me
 ├── Department Files
 ├── Recent
 └── Favorites

Calendar
 ├── My Calendar
 ├── Team Calendar
 └── Company Calendar

Announcements

Quick Notes

Attendance

Leave

Payroll

HRMS Modules
```

Use dynamic menu visibility based on RBAC.

---

# 17. Real-Time Architecture

The communication system should be designed for real-time functionality.

Preferred architecture:

```text
Laravel
   │
   ├── REST/API
   ├── Livewire
   ├── Events
   ├── Notifications
   └── WebSockets
          │
          ▼
     Real-time Chat
```

Use Laravel's recommended broadcasting architecture compatible with the existing project.

Do not introduce unnecessary infrastructure unless required.

---

# 18. Database Architecture

Create properly normalized tables.

Potential tables:

```text
teams
team_members
channels
channel_members
conversations
conversation_members
messages
message_reactions
message_attachments
message_mentions
files
file_folders
file_versions
file_permissions
file_shares
calendar_events
calendar_event_attendees
meeting_agendas
meeting_notes
notifications
announcement_reads
notes
note_shares
```

Before creating any table:

1. Inspect the existing database.
2. Identify existing tables.
3. Reuse existing employee/user relationships.
4. Avoid duplicate data.
5. Follow the existing naming conventions.

---

# 19. Security Requirements

This is an internal enterprise HRMS and contains sensitive company information.

Implement:

* RBAC
* Permission checks
* Tenant/company isolation if already supported
* File access authorization
* Private file storage
* Secure download URLs/routes
* Validation
* CSRF protection
* XSS protection
* SQL injection protection
* Upload MIME validation
* File-size restrictions
* Audit logs
* Login/session security
* Rate limiting where appropriate

Never expose uploaded files directly through publicly accessible directories when authorization is required.

---

# 20. LAN Architecture

The system currently runs internally.

Example:

```text
HRMS Server
192.168.29.101

        │
        ├── Employees
        ├── HR
        ├── Management
        └── Departments
```

The application must work completely inside the LAN.

Do not make external cloud services mandatory for:

* Chat
* Files
* Calendar
* Notifications
* Internal collaboration

The architecture should be capable of future cloud deployment, but the current deployment must work on-premise.

---

# 21. Existing ZKTeco Integration

IMPORTANT:

The existing HRMS has attendance integration with a **ZKTeco biometric device**.

Do NOT break or modify the existing biometric integration unnecessarily.

Attendance flow must continue working:

```text
ZKTeco Device
      ↓
Local Integration Machine
      ↓
Existing Attendance System
      ↓
HRMS
```

Any new communication/collaboration development must remain isolated from this functionality.

If changes to attendance architecture are required, document them before implementation.

---

# 22. UI/UX Requirements

The current project uses:

```text
Laravel
Livewire
Existing AdminLTE-based UI
```

Upgrade the UI toward a modern enterprise SaaS experience.

Requirements:

* Responsive
* Desktop-first
* Mobile responsive
* Clean sidebar
* Modern cards
* Modern tables
* Modal/drawer components
* Toast notifications
* Skeleton loaders
* Empty states
* Confirmation dialogs
* Keyboard-friendly interactions
* Dark mode ready
* Consistent spacing
* Consistent typography
* Accessibility-conscious

Avoid excessive animations.

Performance is more important than visual effects.

---

# 23. Development Strategy

Before writing code:

### Step 1 — Audit

Analyze:

* Existing Laravel version
* PHP version
* Livewire version
* Existing AdminLTE/theme
* Database schema
* Authentication
* RBAC
* Employee relationships
* Existing APIs
* Existing attendance integration
* Existing notification system
* Existing file handling

### Step 2 — Architecture

Prepare:

* Module architecture
* Database ERD
* Routes
* Policies
* Models
* Services
* Events
* Listeners
* Notifications
* Storage strategy
* Permission matrix

### Step 3 — Implementation

Develop module-by-module:

1. Communication foundation
2. Teams & Channels
3. Chat
4. File Management
5. Calendar
6. Meetings
7. Notifications
8. Global Search
9. Dashboard integration
10. UI modernization

### Step 4 — Testing

Test:

* Authentication
* Permissions
* File access
* Chat
* Notifications
* Calendar
* Search
* Mobile responsiveness
* Existing attendance
* ZKTeco integration
* Existing HRMS modules

---

# 24. Important Development Rules

DO NOT:

* Rebuild existing HRMS modules
* Create duplicate employee tables
* Break existing attendance
* Break ZKTeco integration
* Remove existing functionality
* Modify production database without migration
* Hard-code permissions
* Store confidential files publicly
* Hard-code department IDs
* Hard-code employee IDs
* Introduce unnecessary packages
* Replace Laravel architecture unnecessarily

DO:

* Reuse existing models
* Reuse authentication
* Reuse RBAC
* Reuse existing components where appropriate
* Create reusable Livewire components
* Follow SOLID principles
* Use service classes where appropriate
* Use policies for authorization
* Use events/listeners for asynchronous actions
* Write migrations
* Write tests for critical functionality
* Keep modules maintainable
* Keep the system future-cloud-ready

---

# 25. Final Goal

Transform the existing HRMS from:

**"HR Management System"**

into:

**"Employee Digital Workplace / Internal Collaboration Platform"**

where employees can manage:

```text
HR
Attendance
Leave
Payroll
Tasks
Tickets
Suggestions
Announcements
Chat
Teams
Channels
Files
Calendar
Meetings
Notes
Notifications
```

from a single unified platform.

The final product should feel like a **professional enterprise workplace platform**, not an old-style HR portal.

Prioritize:

**Security → Existing-system stability → Architecture → Performance → UX → Visual polish.**
