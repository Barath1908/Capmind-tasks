import {all,call,put,takeEvery,takeLatest,select} from "redux-saga/effects";
import * as types from "./types";
import {getPatients,getPatientDetails,registerPatient} from "../api/patientApi";
import {setPatients,setPatientDetails,queuePatientForm,removeQueuedForm,setLoading,setError} from "./actions";

// ---------------- Fetch Patients ----------------

function* fetchPatientsWorker() {
  try {
    yield put(setLoading(true));

    const response = yield call(getPatients);

    // Store all 10 patients
    yield put(setPatients(response.data));

    yield put(setError(null));
  } catch (error) {
    yield put(setError(error.message));
  } finally {
    yield put(setLoading(false));
  }
}

// ---------------- Submit Form ----------------

function* submitPatientWorker(action) {
  try {
    if (!navigator.onLine) {
      yield put(queuePatientForm(action.payload));
      return;
    }

    yield call(registerPatient, action.payload);

  } catch (error) {
    // Save to queue if API fails
    yield put(queuePatientForm(action.payload));
  }
}

// ---------------- Process Offline Queue ----------------

function* processQueueWorker() {
  try {
    const queue = yield select((state) => state.offlineQueue);

    for (let i = 0; i < queue.length; i++) {
      try {
        yield call(registerPatient, queue[i]);

        yield put(removeQueuedForm(i));

      } catch (error) {
        break;
      }
    }

  } catch (error) {
    console.log(error);
  }
}

// ---------------- Patient Details ----------------

function* fetchPatientDetailsWorker(action) {
  try {
    yield put(setLoading(true));

    const response = yield call(getPatientDetails,action.payload);

    yield put(setPatientDetails(response.data));

    yield put(setError(null));

  } catch (error) {
    yield put(setError(error.message));

  } finally {
    yield put(setLoading(false));
  }
}

// ---------------- Root Saga ----------------

export default function* rootSaga() {
  yield all([
    // Fetch patient list
    takeLatest(
      types.FETCH_PATIENTS,
      fetchPatientsWorker
    ),

    // Submit form
    takeEvery(
      types.SUBMIT_PATIENT_FORM,
      submitPatientWorker
    ),

    // Process queue
    takeEvery(
      types.PROCESS_QUEUE,
      processQueueWorker
    ),

    // Request cancellation
    takeLatest(
      types.FETCH_PATIENT_DETAILS,
      fetchPatientDetailsWorker
    )
  ]);
}