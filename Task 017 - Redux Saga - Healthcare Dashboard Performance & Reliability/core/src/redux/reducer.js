import * as types from "./types";

const initialState = {
  patients: [],
  currentPage: 1,
  pageSize: 5,

  patientDetails: null,

  offlineQueue: [],

  loading: false,
  error: null,
};

const reducer = (state = initialState, action) => {
  switch (action.type) {
    // ---------------- Patients ----------------

    case types.SET_PATIENTS:
      return {...state, patients: action.payload,};

    // ---------------- Pagination ----------------

    case types.NEXT_PAGE:
      return {...state, currentPage: state.currentPage + 1,};

    case types.PREVIOUS_PAGE:
      return {...state, currentPage: state.currentPage > 1 ? state.currentPage - 1 : 1,};

    // ---------------- Queue ----------------

    case types.QUEUE_PATIENT_FORM:
      return {...state, offlineQueue: [...state.offlineQueue, action.payload],};

    case types.REMOVE_QUEUED_FORM:
      return {...state, offlineQueue: state.offlineQueue.filter(
          (_, index) => index !== action.payload),
      };

    // ---------------- Patient Details ----------------

    case types.SET_PATIENT_DETAILS:
      return {...state, patientDetails: action.payload,};

    // ---------------- Loading ----------------

    case types.SET_LOADING:
      return {...state,loading: action.payload,};

    // ---------------- Error ----------------

    case types.SET_ERROR:
      return {...state,error: action.payload,};

    default:
      return state;
  }
};

export default reducer;