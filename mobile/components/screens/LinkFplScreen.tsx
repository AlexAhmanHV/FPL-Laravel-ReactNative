// mobile/screens/LinkFplScreen.tsx
import React, { useState } from 'react';
import { SafeAreaView, View, Text, StyleSheet } from 'react-native';
import { TextField } from '../ui/TextField';
import { PrimaryButton } from '../ui/PrimaryButton';
import { apiClient } from '../../api/client';
import { useNavigation } from '@react-navigation/native';

export const LinkFplScreen: React.FC = () => {
  const [fplId, setFplId] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [info, setInfo] = useState<string | null>(null);

  const navigation = useNavigation<any>();

  const handleSave = async () => {
    const trimmed = fplId.trim();
    if (!trimmed || isNaN(Number(trimmed))) {
      setError('Skriv ett giltigt FPL-ID (bara siffror).');
      return;
    }

    try {
      setLoading(true);
      setError(null);
      setInfo(null);

      // 1) Spara FPL-ID på usern
      await apiClient.post('/me/link-fpl', {
        fpl_entry_id: Number(trimmed),
      });

      // 2) Synca laget
      await apiClient.post('/me/sync-team');

      setInfo('FPL-ID sparat och lag synkat!');

      setTimeout(() => {
        navigation.navigate('MyTeam');
      }, 800);
    } catch (e: any) {
      console.log('link fpl error', e?.response?.data ?? e?.message);
      setError('Kunde inte spara FPL-ID eller synka laget.');
    } finally {
      setLoading(false);
    }
  };

  return (
    <SafeAreaView style={styles.safe}>
      <View style={styles.container}>
        <Text style={styles.title}>Koppla ditt FPL-lag</Text>
        <Text style={styles.subtitle}>
          Skriv in ditt FPL Entry ID så hämtar vi ditt nuvarande lag.
        </Text>

        <View style={{ marginTop: 16 }}>
          <TextField
            label="FPL Entry ID"
            value={fplId}
            onChangeText={setFplId}
            keyboardType="number-pad"
            placeholder="1234567"
          />
        </View>

        {error && <Text style={styles.error}>{error}</Text>}
        {info && <Text style={styles.info}>{info}</Text>}

        <View style={{ marginTop: 20 }}>
          <PrimaryButton
            title="Spara & synka lag"
            onPress={handleSave}
            loading={loading}
          />
        </View>

        <Text style={styles.help}>
          Du hittar ditt FPL-ID genom att öppna ditt lag i webbläsaren – numret i URL:en är ditt entry ID.
        </Text>
      </View>
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  safe: {
    flex: 1,
    backgroundColor: '#020617',
  },
  container: {
    flex: 1,
    padding: 20,
    justifyContent: 'center',
  },
  title: {
    fontSize: 24,
    fontWeight: '700',
    color: '#f9fafb',
  },
  subtitle: {
    fontSize: 14,
    color: '#9ca3af',
    marginTop: 8,
  },
  error: {
    color: '#fecaca',
    marginTop: 8,
  },
  info: {
    color: '#bbf7d0',
    marginTop: 8,
  },
  help: {
    marginTop: 16,
    fontSize: 12,
    color: '#64748b',
  },
});
