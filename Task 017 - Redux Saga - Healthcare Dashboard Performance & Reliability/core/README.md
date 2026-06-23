# Redux Saga - Healthcare Dashboard Performance & Reliability

## Overview

This project is a Healthcare Dashboard built using **React**, **Redux**, and **Redux-Saga**. It demonstrates real-world Redux-Saga concepts such as:

* Pagination optimization
* Offline form submission queue
* API request cancellation
* Redux-Saga middleware architecture
* Loading and error handling

---

## Technologies Used

* React
* Redux
* Redux-Saga
* Axios
* JSONPlaceholder API

---

## Project Structure

```text
src
│
├── api
│     └── patientApi.js
│
├── redux
│     ├── actions.js
│     ├── reducer.js
│     ├── saga.js
│     ├── store.js
│     └── types.js
│
├── components
│     ├── PatientList.js
│     ├── PatientForm.js
│     ├── PatientDetails.js
│     └── NetworkStatus.js
│
├── App.js
├── App.css
└── index.js
```

---

# Feature 1 – Pagination Optimization

## Objective

Reduce unnecessary API calls and improve performance.

### Flow

```text
Component
    ↓
FETCH_PATIENTS
    ↓
Redux Saga
    ↓
API Call (10 patients)
    ↓
SET_PATIENTS
    ↓
Redux Store
    ↓
Display only 5 records
```

### Implementation

* API is called only once.
* All 10 patients are stored inside Redux Store.
* UI displays 5 patients per page.
* Clicking Next/Previous changes the visible records without making another API request.

### Benefit

* Faster navigation
* Reduced API calls
* Better performance

---

# Feature 2 – Offline Form Submission Queue

## Objective

Ensure form submissions are not lost when the internet connection is unavailable.

### Online Flow

```text
Submit Form
    ↓
SUBMIT_PATIENT_FORM
    ↓
Saga Worker
    ↓
API Call
    ↓
Success
```

### Offline Flow

```text
Submit Form
    ↓
Internet unavailable
    ↓
QUEUE_PATIENT_FORM
    ↓
Redux Store (offlineQueue)
```

Example:

```js
offlineQueue = [
  {
    name: "John",
    age: 45,
    disease: "Diabetes",
    doctor: "Dr Smith"
  }
]
```

### Queue Processing

When internet connectivity is restored:

```text
Browser Online Event
        ↓
PROCESS_QUEUE
        ↓
Saga Worker
        ↓
Read offlineQueue
        ↓
Send pending requests
        ↓
Remove successful entries
```

### Benefit

* Prevents data loss
* Supports unstable network conditions
* Improves reliability

---

# Feature 3 – API Request Cancellation

## Objective

Prevent unnecessary requests when users switch quickly between patients.

### Example

Doctor clicks:

```text
Patient A
```

Request starts.

Before completion:

```text
Patient B
```

Doctor changes again:

```text
Patient C
```

Previous requests are cancelled automatically.

Only the latest request is processed.

### Implementation

Using:

```js
takeLatest(
  FETCH_PATIENT_DETAILS,
  fetchPatientDetailsWorker
)
```

### Flow

```text
FETCH_PATIENT_DETAILS(1)
        ↓
Request started

FETCH_PATIENT_DETAILS(5)
        ↓
Previous request cancelled

Only latest response reaches reducer
```

### Benefit

* Better user experience
* Prevents stale data
* Avoids unnecessary API responses

---

# Redux-Saga Architecture

```text
Component
      ↓
Dispatch Action
      ↓
Watcher Saga
      ↓
Worker Saga
      ↓
API Call / Queue Logic
      ↓
Reducer
      ↓
Redux Store Updated
      ↓
UI Re-render
```

---

# Redux-Saga Effects Used

### takeLatest()

Used for:

* Fetching patients
* Fetching patient details

Purpose:

* Cancels previous requests and processes only the latest one.

---

### takeEvery()

Used for:

* Form submission
* Queue processing

Purpose:

* Allows every action to be handled independently.

---

### call()

Used for:

* API requests

Purpose:

* Executes asynchronous functions.

---

### put()

Used for:

* Dispatching Redux actions from sagas.

---

### select()

Used for:

* Reading `offlineQueue` from Redux Store.

---

### all()

Used for:

* Running multiple watcher sagas simultaneously.

---

# Features Implemented

✅ Pagination optimization

✅ Offline queue handling

✅ Automatic queue processing when network returns

✅ API request cancellation using `takeLatest()`

✅ Redux-Saga middleware

✅ Loading indicator

✅ Error handling

✅ Network status indicator

✅ Responsive UI

---

# Installation

```bash
npm install
```

Install dependencies:

```bash
npm install redux react-redux redux-saga axios
```

Start application:

```bash
npm start
```

---

# Conclusion

This project demonstrates practical Redux-Saga patterns commonly used in enterprise healthcare applications. It focuses on improving performance, reliability, and user experience through pagination optimization, offline queue handling, and request cancellation.
