import React from 'react';
import {
  SafeAreaView,
  View,
  Text,
  KeyboardAvoidingView,
  Platform,
  ScrollView,
  StyleSheet,
} from 'react-native';
import { TextField } from '../ui/TextField';
import { PrimaryButton } from '../ui/PrimaryButton';

interface LoginScreenProps {
  email: string;
  password: string;
  loading: boolean;
  error: string | null;
  infoMessage: string | null;
  onChangeEmail: (value: string) => void;
  onChangePassword: (value: string) => void;
  onSubmit: () => void;
  onPressRegister: () => void;
}

export const LoginScreen: React.FC<LoginScreenProps> = ({
  email,
  password,
  loading,
  error,
  infoMessage,
  onChangeEmail,
  onChangePassword,
  onSubmit,
  onPressRegister,
}) => {
  console.log('### RENDERING LoginScreen (FPL Sidekick) ###');

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
          {/* Background accents */}
          <View style={styles.bgAccentTop} />
          <View style={styles.bgAccentBottom} />

          <View style={styles.card}>
            {/* Brand / header */}
            <View style={styles.brandRow}>
              <View style={styles.logoDot} />
              <Text style={styles.logoText}>FPL Sidekick</Text>
            </View>

            <Text style={styles.title}>Welcome back</Text>
            <Text style={styles.subtitle}>
              Log in to sync your FPL squad, track gameweeks, and get smarter about transfers.
            </Text>

            {/* Success / info message */}
            {infoMessage && (
              <View style={styles.infoBox}>
                <Text style={styles.infoText}>{infoMessage}</Text>
              </View>
            )}

            {/* Form */}
            <View style={styles.form}>
              <TextField
                label="Email"
                value={email}
                onChangeText={onChangeEmail}
                autoCapitalize="none"
                keyboardType="email-address"
                placeholder="you@example.com"
              />

              <TextField
                label="Password"
                value={password}
                onChangeText={onChangePassword}
                secureTextEntry
                placeholder="Enter your password"
              />

              {error && <Text style={styles.error}>{error}</Text>}

              <View style={styles.buttonWrapper}>
                <PrimaryButton
                  title="Continue"
                  onPress={onSubmit}
                  loading={loading}
                />
              </View>
            </View>

            {/* Register link */}
            <View style={styles.registerRow}>
              <Text style={styles.registerText}>Don&apos;t have an account?</Text>
              <Text style={styles.registerLink} onPress={onPressRegister}>
                Register here
              </Text>
            </View>
          </View>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  flex: {
    flex: 1,
  },
  safe: {
    flex: 1,
    backgroundColor: '#020617', // slate-950
  },
  centeredContent: {
    flexGrow: 1,
    justifyContent: 'center',
    padding: 20,
  },

  bgAccentTop: {
    position: 'absolute',
    top: -80,
    right: -40,
    width: 200,
    height: 200,
    borderRadius: 999,
    backgroundColor: 'rgba(56, 189, 248, 0.25)',
  },
  bgAccentBottom: {
    position: 'absolute',
    bottom: -60,
    left: -40,
    width: 220,
    height: 220,
    borderRadius: 999,
    backgroundColor: 'rgba(34, 197, 94, 0.22)',
  },

  card: {
    backgroundColor: 'rgba(15, 23, 42, 0.96)',
    borderRadius: 20,
    padding: 24,
    borderWidth: 1,
    borderColor: 'rgba(148, 163, 184, 0.35)',
    shadowColor: '#000',
    shadowOpacity: 0.45,
    shadowRadius: 18,
    shadowOffset: { width: 0, height: 18 },
    elevation: 12,
  },

  brandRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 16,
  },
  logoDot: {
    width: 14,
    height: 14,
    borderRadius: 999,
    backgroundColor: '#22c55e',
    marginRight: 8,
  },
  logoText: {
    fontSize: 16,
    fontWeight: '600',
    color: '#e5e7eb',
    letterSpacing: 0.8,
  },

  title: {
    fontSize: 26,
    fontWeight: '700',
    marginBottom: 6,
    color: '#f9fafb',
  },
  subtitle: {
    fontSize: 14,
    color: '#9ca3af',
    marginBottom: 22,
  },

  infoBox: {
    borderRadius: 10,
    paddingVertical: 8,
    paddingHorizontal: 10,
    backgroundColor: 'rgba(22, 163, 74, 0.2)', // green-ish
    borderWidth: 1,
    borderColor: 'rgba(22, 163, 74, 0.6)',
    marginBottom: 16,
  },
  infoText: {
    fontSize: 13,
    color: '#bbf7d0', // green-200
  },

  form: {
    marginBottom: 18,
  },

  error: {
    color: '#fecaca',
    marginTop: 4,
    fontSize: 13,
  },

  buttonWrapper: {
    marginTop: 16,
  },

  registerRow: {
    marginTop: 10,
    flexDirection: 'row',
    justifyContent: 'center',
  },
  registerText: {
    fontSize: 13,
    color: '#9ca3af',
    marginRight: 4,
  },
  registerLink: {
    fontSize: 13,
    color: '#22c55e',
    fontWeight: '600',
  },
});
