// app/(tabs)/index.tsx
import React, { useState, useEffect } from 'react';
import {
  SafeAreaView,
  Text,
  ActivityIndicator,
  ScrollView,
  StyleSheet,
  View,
} from 'react-native';

import { login, register } from '../../api/auth';
import { setAuthToken } from '../../api/client';
import {
  fetchMeSummary,
  resendVerificationEmail,
  linkFpl,
  syncMyTeam,
  fetchMyTeam,
} from '../../api/user';

import { LoginScreen } from '../../components/screens/LoginScreen';
import { RegisterScreen } from '../../components/screens/RegisterScreen';
import { PrimaryButton } from '../../components/ui/PrimaryButton';

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

type AuthMode = 'login' | 'register';

// --------- Component ---------

export default function Index() {
  const [token, setToken] = useState<string | null>(null);
  const [authMode, setAuthMode] = useState<AuthMode>('login');

  const [name, setName] = useState<string>('');
  const [email, setEmail] = useState<string>('');
  const [password, setPassword] = useState<string>('');
  const [confirmPassword, setConfirmPassword] = useState<string>('');

  const [loading, setLoading] = useState<boolean>(false);
  const [summary, setSummary] = useState<MeSummary | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [infoMessage, setInfoMessage] = useState<string | null>(null);
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
      setInfoMessage(null);
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

  const handleRegister = async () => {
    try {
      setError(null);
      setInfoMessage(null);

      if (!name || !email || !password || !confirmPassword) {
        setError('Please fill in all fields.');
        return;
      }

      if (password !== confirmPassword) {
        setError('Passwords do not match.');
        return;
      }

      setLoading(true);
      await register(name, email, password, confirmPassword);

      // Registration succeeded:
      // - Go back to login
      // - Show success message
      // - Clear passwords (keep email so they can log in easily)
      setAuthMode('login');
      setPassword('');
      setConfirmPassword('');
      setInfoMessage(
        'Account created successfully. Check your email for a verification link, then log in.'
      );
    } catch (err: any) {
      console.log(err?.response?.data ?? err?.message ?? err);
      setError('Registration failed');
    } finally {
      setLoading(false);
    }
  };

  const handleRegisterPress = () => {
    setError(null);
    setInfoMessage(null);
    setAuthMode('register');
  };

  const handleGoToLogin = () => {
    setError(null);
    setInfoMessage(null);
    setAuthMode('login');
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
      await loadSummary();
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

  // 1) Not logged in → show auth screens
  if (!token) {
    if (authMode === 'login') {
      return (
        <LoginScreen
          email={email}
          password={password}
          loading={loading}
          error={error}
          infoMessage={infoMessage}
          onChangeEmail={setEmail}
          onChangePassword={setPassword}
          onSubmit={handleLogin}
          onPressRegister={handleRegisterPress}
        />
      );
    }

    // register mode
    return (
      <RegisterScreen
        name={name}
        email={email}
        password={password}
        confirmPassword={confirmPassword}
        loading={loading}
        error={error}
        onChangeName={setName}
        onChangeEmail={setEmail}
        onChangePassword={setPassword}
        onChangeConfirmPassword={setConfirmPassword}
        onSubmit={handleRegister}
        onPressGoToLogin={handleGoToLogin}
      />
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

  // 3) Email not verified → verify screen
  if (!email_verified) {
    return (
      <SafeAreaView style={styles.safe}>
        <ScrollView contentContainerStyle={styles.screenContainer}>
          <Text style={styles.title}>Hey {user.name}</Text>
          <Text style={styles.subtitle}>
            You need to verify your email ({user.email}) before continuing.
          </Text>

          {error && <Text style={styles.error}>{error}</Text>}

          <View style={styles.buttonWrapper}>
            <PrimaryButton
              title="Resend verification email"
              onPress={handleResendVerification}
              loading={loading}
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

          {error && <Text style={styles.error}>{error}</Text>}
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
            <PrimaryButton
              title="Sync my team"
              onPress={handleSyncTeam}
              loading={loading}
            />
          </View>
        </ScrollView>
      </SafeAreaView>
    );
  }

  // 6) Everything ready → home state
  return (
    <SafeAreaView style={styles.safe}>
      <ScrollView contentContainerStyle={styles.screenContainer}>
        <Text style={styles.title}>Welcome back, {user.name}</Text>
        <Text style={styles.subtitle}>
          Your FPL entry ID: {user.fpl_entry_id}
        </Text>

        {error && <Text style={styles.error}>{error}</Text>}

        <View style={styles.buttonWrapper}>
          <PrimaryButton
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
  safe: {
    flex: 1,
    backgroundColor: '#f3f4f6',
  },
  screenContainer: {
    flexGrow: 1,
    padding: 16,
    justifyContent: 'flex-start',
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
