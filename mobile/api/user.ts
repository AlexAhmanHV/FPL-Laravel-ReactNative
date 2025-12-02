// mobile/api/user.ts
import { apiClient } from './client';
import type { AuthUser } from './auth';

// --------- Types ---------

export interface SummaryUser extends AuthUser {}

export interface MeSummary {
  user: SummaryUser;
  email_verified: boolean;
  has_fpl_entry_id: boolean;
  has_team: boolean;
}

export type Position = 'GKP' | 'DEF' | 'MID' | 'FWD';

export interface Club {
  id: number;
  name: string;
  short_name: string;
  code?: string | null;
}

export interface Player {
  id: number;
  web_name: string;
  position: Position;
  club?: Club;
}

export interface SquadSlot {
  id: number;
  team_id: number;
  player_id: number;
  position: Position;
  is_starting: boolean;
  order: number;
  player?: Player;
}

export interface Team {
  id: number;
  user_id: number;
  name: string;
  fpl_entry_id?: number | null;
  squad_slots?: SquadSlot[];
}

// --------- API functions ---------

// If your backend has /api/me/summary (recommended)
export async function fetchMeSummary(): Promise<MeSummary> {
  const { data } = await apiClient.get<MeSummary>('/me/summary');
  return data;
}

// Fallback if you only have /api/me right now:
// (uncomment this and adjust Index.tsx if needed)
/*
export async function fetchMeSummary(): Promise<MeSummary> {
  const { data } = await apiClient.get<AuthUser>('/me');

  return {
    user: data,
    email_verified: !!data.email_verified_at,
    has_fpl_entry_id: data.fpl_entry_id != null,
    has_team: false, // until you wire real flag from backend
  };
}
*/

export async function resendVerificationEmail(): Promise<void> {
  // Default Laravel email verification notification endpoint
  await apiClient.post('/email/verification-notification');
}

export async function linkFpl(entryId: number): Promise<void> {
  await apiClient.post('/me/link-fpl', { entry_id: entryId });
}

export async function syncMyTeam(): Promise<void> {
  await apiClient.post('/me/sync-team');
}

export async function fetchMyTeam(): Promise<Team | null> {
  const { data } = await apiClient.get<Team | null>('/my-team');
  return data;
}
