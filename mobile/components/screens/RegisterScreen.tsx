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

interface RegisterScreenProps {
  name: string;
  email: string;
  password: string;
  confirmPassword: string;
  loading: boolean;
  error: string | null;
  onChangeName: (value: string) => void;
  onChangeEmail: (value: string) => void;
  onChangePassword: (value: string) => void;
  onChangeConfirmPassword: (value: string) => void;
  onSubmit: () => void;
  onPressGoToLogin: () => void;
}

export const RegisterScreen: React.FC<RegisterScreenProps> = ({
  name,
  email,
  password,
  confirmPassword,
  loading,
  error,
  onChangeName,
  onChangeEmail,
  onChangePassword,
  onChangeConfirmPassword,
  onSubmit,
  onPressGoToLogin,
}) => {
  console.log('### RENDERING RegisterScreen (FPL Sidekick) ###');

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

            <Text style={styles.title}>Create your account</Text>
            <Text style={styles.subtitle}>
              Set up your Sidekick profile so we can keep track of your FPL seasons.
            </Text>

            <View style={styles.form}>
              <TextField
                label="Name"
                value={name}
                onChangeText={onChangeName}
                placeholder="What should we call you?"
              />

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
                placeholder="Choose a strong password"
              />

              <TextField
                label="Confirm password"
                value={confirmPassword}
                onChangeText={onChangeConfirmPassword}
                secureTextEntry
                placeholder="Type your password again"
              />

              {error && <Text style={styles.error}>{error}</Text>}

              <View style={styles.buttonWrapper}>
                <PrimaryButton
                  title="Create account"
                  onPress={onSubmit}
                  loading={loading}
                />
              </View>
            </View>

            <View style={styles.registerRow}>
              <Text style={styles.registerText}>Already have an account?</Text>
              <Text style={styles.registerLink} onPress={onPressGoToLogin}>
                Log in
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
    backgroundColor: '#020617',
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
