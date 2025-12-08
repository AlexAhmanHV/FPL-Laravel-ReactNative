// mobile/screens/MyTeamScreen.tsx
import React, { useEffect, useState } from 'react';
import { SafeAreaView, View, Text, StyleSheet, FlatList, ActivityIndicator } from 'react-native';
import { apiClient } from '../../api/client';

export const MyTeamScreen: React.FC = () => {
  const [team, setTeam] = useState<any | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const loadTeam = async () => {
    try {
      setLoading(true);
      setError(null);

      const res = await apiClient.get('/my-team');
      setTeam(res.data);
    } catch (e: any) {
      console.log('my-team error', e?.response?.data ?? e?.message);
      setError('Kunde inte hämta ditt lag.');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    loadTeam();
  }, []);

  if (loading) {
    return (
      <SafeAreaView style={styles.center}>
        <ActivityIndicator />
        <Text style={{ color: 'white', marginTop: 8 }}>Laddar lag...</Text>
      </SafeAreaView>
    );
  }

  if (error || !team) {
    return (
      <SafeAreaView style={styles.center}>
        <Text style={{ color: '#fecaca' }}>{error ?? 'Inget lag hittades.'}</Text>
      </SafeAreaView>
    );
  }

  const players = team.players || [];

  return (
    <SafeAreaView style={styles.safe}>
      <View style={styles.header}>
        <Text style={styles.title}>{team.name ?? 'Mitt lag'}</Text>
        {team.gameweek && (
          <Text style={styles.subtitle}>Aktiv GW: {team.gameweek.number}</Text>
        )}
      </View>

      <FlatList
        data={players}
        keyExtractor={(item) => String(item.id) + '-' + String(item.order)}
        contentContainerStyle={{ padding: 16 }}
        renderItem={({ item }) => (
          <View style={[styles.card, !item.is_starting && styles.benchCard]}>
            <Text style={styles.playerName}>
              {item.web_name}{' '}
              <Text style={styles.club}>({item.club_short_name ?? item.position})</Text>
            </Text>
            <Text style={styles.meta}>
              {item.position} · {item.price}M {item.is_starting ? '· Start' : '· Bänk'}
            </Text>
            {item.expected_points != null && (
              <Text style={styles.ep}>EP nästa GW: {item.expected_points}</Text>
            )}
          </View>
        )}
      />
    </SafeAreaView>
  );
};

const styles = StyleSheet.create({
  safe: {
    flex: 1,
    backgroundColor: '#020617',
  },
  center: {
    flex: 1,
    backgroundColor: '#020617',
    justifyContent: 'center',
    alignItems: 'center',
  },
  header: {
    paddingHorizontal: 16,
    paddingTop: 16,
  },
  title: {
    fontSize: 22,
    fontWeight: '700',
    color: '#f9fafb',
  },
  subtitle: {
    fontSize: 14,
    color: '#9ca3af',
    marginTop: 4,
  },
  card: {
    backgroundColor: '#111827',
    borderRadius: 12,
    padding: 12,
    marginBottom: 8,
  },
  benchCard: {
    opacity: 0.7,
  },
  playerName: {
    color: '#f9fafb',
    fontSize: 16,
    fontWeight: '600',
  },
  club: {
    color: '#9ca3af',
    fontSize: 13,
  },
  meta: {
    color: '#9ca3af',
    fontSize: 13,
    marginTop: 2,
  },
  ep: {
    color: '#22c55e',
    fontSize: 13,
    marginTop: 4,
  },
});
