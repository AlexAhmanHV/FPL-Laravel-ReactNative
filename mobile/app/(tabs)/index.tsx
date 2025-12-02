// app/(tabs)/index.tsx
import React, { useState, useEffect } from 'react';
import {
  SafeAreaView,
  Text,
  TextInput,
  Button,
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  StyleSheet,
  View,
} from 'react-native';

import { login } from '../../api/auth';
import { setAuthToken } from '../../api/client';
import {
  fetchMeSummary,
  resendVerificationEmail,
  linkFpl,
  syncMyTeam,
  fetchMyTeam,
} from '../../api/user';

// --------- Types ---------

interface SummaryUser {
  name: string;
  email: string;
  fpl_entry_id?: number | null;
}

interface MeSummary {
  user: SummaryUser;
  email_verified: boolean;
  has_fpl_entry_id: boolean;
  has_team: boolean;
}

// --------- Component ---------

export default function Index() {
  const [token, setToken] = useState<string | null>(null);
  const [email, setEmail] = useState<string>('alex@example.com');
  const [password, setPassword] = useState<string>('password123');
  const [loading, setLoading] = useState<boolean>(false);
  const [summary, setSummary] = useState<MeSummary | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [fplIdInput, setFplIdInput] = useState<string>('');

  // After login, load /me/summary
  useEffect(() => {
    if (!token) return;
    setAuthToken(token);
    void loadSummary();
  }, [token]);

  const handleLogin = async () => {
    try {
      setError(null);
      setLoading(true);
      const data = await login(email, password);
      setToken(data.token);
    } catch (err: any) {
      console.log(err?.response?.data ?? err?.message ?? err);
      setError('Login failed');
    } finally {
      setLoading(false);
    }
  };

  const loadSummary = async () => {
    try {
      setError(null);
      setLoading(true);
      const data = await fetchMeSummary();
      setSummary(data);
    } catch (err: any) {
      console.log(err?.response?.data ?? err?.message ?? err);
      setError('Failed to load summary');
    } finally {
      setLoading(false);
    }
  };

  const handleResendVerification = async () => {
    try {
      setError(null);
      setLoading(true);
      await resendVerificationEmail();
    } catch (err: any) {
      console.log(err?.response?.data ?? err?.message ?? err);
      setError('Failed to resend email');
    } finally {
      setLoading(false);
    }
  };

  const handleLinkFpl = async () => {
    try {
      setError(null);
      setLoading(true);

      const id = parseInt(fplIdInput, 10);
      if (!Number.isInteger(id) || id <= 0) {
        setError('Enter a valid numeric FPL ID');
        return;
      }

      await linkFpl(id);
      await loadSummary();
    } catch (err: any) {
      console.log(err?.response?.data ?? err?.message ?? err);
      setError('Failed to link FPL ID');
    } finally {
      setLoading(false);
    }
  };

  const handleSyncTeam = async () => {
    try {
      setError(null);
      setLoading(true);
      await syncMyTeam();
      await loadSummary();
    } catch (err: any) {
      console.log(err?.response?.data ?? err?.message ?? err);
      setError('Failed to sync team');
    } finally {
      setLoading(false);
    }
  };

  const handleViewTeam = async () => {
    try {
      setError(null);
      setLoading(true);
      const team = await fetchMyTeam();
      console.log('My team:', team);
    } catch (err: any) {
      console.log(err?.response?.data ?? err?.message ?? err);
      setError('Failed to load team');
    } finally {
      setLoading(false);
    }
  };

  // ─────────────
  // Screens
  // ─────────────

  // 1) Not logged in → nice clean login screen
  if (!token) {
    return (
      <SafeAreaView style={styles.safe}>
        <KeyboardAvoidingView
          style={styles.flex}
          behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        >
          <ScrollView
            contentContainerStyle={styles.centeredContent}
            keyboardShouldPersistTaps="handled"
          >
            <View style={styles.card}>
              <Text style={styles.title}>Log in to FPL Helper</Text>
              <Text style={styles.subtitle}>
                Use the same email and password you registered in the app backend.
              </Text>

              <Text style={styles.label}>Email</Text>
              <TextInput
                value={email}
                onChangeText={setEmail}
                autoCapitalize="none"
                keyboardType="email-address"
                placeholder="you@example.com"
                placeholderTextColor="#999"
                style={styles.input}
              />

              <Text style={styles.label}>Password</Text>
              <TextInput
                value={password}
                onChangeText={setPassword}
                secureTextEntry
                placeholder="••••••••"
                placeholderTextColor="#999"
                style={styles.input}
              />

              {error && <Text style={styles.error}>{error}</Text>}

              <View style={styles.buttonWrapper}>
                <Button
                  title={loading ? 'Logging in...' : 'Login'}
                  onPress={handleLogin}
                  disabled={loading}
                />
              </View>
            </View>
          </ScrollView>
        </KeyboardAvoidingView>
      </SafeAreaView>
    );
  }

  // 2) Logged in but summary not loaded yet
  if (!summary || loading) {
    return (
      <SafeAreaView style={styles.safe}>
        <View style={styles.loadingContainer}>
          <ActivityIndicator />
          <Text style={styles.loadingText}>Loading...</Text>
          {error && <Text style={styles.error}>{error}</Text>}
        </View>
      </SafeAreaView>
    );
  }

  const { user, email_verified, has_fpl_entry_id, has_team } = summary;

  // 3) Email not verified → show verify screen
  if (!email_verified) {
    return (
      <SafeAreaView style={styles.safe}>
        <ScrollView contentContainerStyle={styles.screenContainer}>
          <Text style={styles.title}>Hey {user.name} 👋</Text>
          <Text style={styles.subtitle}>
            You need to verify your email ({user.email}) before continuing.
          </Text>

          {error && <Text style={styles.error}>{error}</Text>}

          <View style={styles.buttonWrapper}>
            <Button
              title={loading ? 'Sending...' : 'Resend verification email'}
              onPress={handleResendVerification}
              disabled={loading}
            />
          </View>
        </ScrollView>
      </SafeAreaView>
    );
  }

  // 4) Email verified but no FPL ID yet
  if (!has_fpl_entry_id) {
    return (
      <SafeAreaView style={styles.safe}>
        <ScrollView contentContainerStyle={styles.screenContainer}>
          <Text style={styles.title}>Link your FPL team</Text>
          <Text style={styles.subtitle}>
            Open the official FPL site, go to your team page and copy the number
            in the URL (the entry ID).
          </Text>

          <Text style={styles.label}>FPL Entry ID</Text>
          <TextInput
            value={fplIdInput}
            onChangeText={setFplIdInput}
            keyboardType="numeric"
            placeholder="1234567"
            placeholderTextColor="#999"
            style={styles.input}
          />

          {error && <Text style={styles.error}>{error}</Text>}

          <View style={styles.buttonWrapper}>
            <Button
              title={loading ? 'Linking...' : 'Link FPL ID'}
              onPress={handleLinkFpl}
              disabled={loading}
            />
          </View>
        </ScrollView>
      </SafeAreaView>
    );
  }

  // 5) FPL ID linked but no team yet
  if (!has_team) {
    return (
      <SafeAreaView style={styles.safe}>
        <ScrollView contentContainerStyle={styles.screenContainer}>
          <Text style={styles.title}>Almost there!</Text>
          <Text style={styles.subtitle}>
            We know your FPL ID ({user.fpl_entry_id}). Now sync your team from FPL.
          </Text>

          {error && <Text style={styles.error}>{error}</Text>}

          <View style={styles.buttonWrapper}>
            <Button
              title={loading ? 'Syncing...' : 'Sync my team'}
              onPress={handleSyncTeam}
              disabled={loading}
            />
          </View>
        </ScrollView>
      </SafeAreaView>
    );
  }

  // 6) Everything ready → "home" state
  return (
    <SafeAreaView style={styles.safe}>
      <ScrollView contentContainerStyle={styles.screenContainer}>
        <Text style={styles.title}>Welcome back, {user.name} 👋</Text>
        <Text style={styles.subtitle}>
          Your FPL entry ID: {user.fpl_entry_id}
        </Text>

        {error && <Text style={styles.error}>{error}</Text>}

        <View style={styles.buttonWrapper}>
          <Button
            title="View my team (console.log for now)"
            onPress={handleViewTeam}
          />
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

// --------- Styles ---------

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  safe: {
    flex: 1,
    backgroundColor: '#f3f4f6', // light gray
  },
  centeredContent: {
    flexGrow: 1,
    justifyContent: 'center',
    padding: 16,
  },
  screenContainer: {
    flexGrow: 1,
    padding: 16,
    justifyContent: 'flex-start',
  },
  card: {
    backgroundColor: '#ffffff',
    borderRadius: 12,
    padding: 20,
    shadowColor: '#000',
    shadowOpacity: 0.08,
    shadowRadius: 8,
    shadowOffset: { width: 0, height: 4 },
    elevation: 3,
  },
  title: {
    fontSize: 24,
    fontWeight: '600',
    marginBottom: 8,
    color: '#111827',
  },
  subtitle: {
    fontSize: 14,
    color: '#4b5563',
    marginBottom: 20,
  },
  label: {
    fontSize: 14,
    color: '#374151',
    marginBottom: 4,
    marginTop: 12,
  },
  input: {
    borderWidth: 1,
    borderColor: '#d1d5db',
    borderRadius: 8,
    paddingHorizontal: 12,
    paddingVertical: 10,
    backgroundColor: '#ffffff',
    fontSize: 16,
  },
  error: {
    color: '#b91c1c',
    marginTop: 8,
    fontSize: 14,
  },
  buttonWrapper: {
    marginTop: 20,
  },
  loadingContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    padding: 16,
  },
  loadingText: {
    marginTop: 8,
    fontSize: 16,
    color: '#374151',
  },
});
