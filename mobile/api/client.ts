// mobile/api/client.ts
import axios from 'axios';

// ⚠️ Adjust this for your setup:
// - iOS simulator: usually http://127.0.0.1:8000/api
// - Android emulator: http://10.0.2.2:8000/api
// - Physical device: http://10.0.1.171:8000/api (your Mac's LAN IP)
const API_BASE_URL = 'http://10.0.1.171:8000/api';

export const apiClient = axios.create({
  baseURL: API_BASE_URL,
  timeout: 10000,
});

// Set or clear the Authorization header globally
export function setAuthToken(token: string | null): void {
  if (token) {
    apiClient.defaults.headers.common.Authorization = `Bearer ${token}`;
  } else {
    delete apiClient.defaults.headers.common.Authorization;
  }
}
