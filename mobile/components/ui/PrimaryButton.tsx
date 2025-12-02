import React from 'react';
import {
  Text,
  TouchableOpacity,
  ActivityIndicator,
  StyleSheet,
  GestureResponderEvent,
} from 'react-native';

interface PrimaryButtonProps {
  title: string;
  onPress: (event: GestureResponderEvent) => void;
  loading?: boolean;
  disabled?: boolean;
}

export const PrimaryButton: React.FC<PrimaryButtonProps> = ({
  title,
  onPress,
  loading = false,
  disabled = false,
}) => {
  const isDisabled = disabled || loading;

  return (
    <TouchableOpacity
      style={[styles.button, isDisabled && styles.buttonDisabled]}
      onPress={onPress}
      disabled={isDisabled}
      activeOpacity={0.8}
    >
      {loading ? (
        <ActivityIndicator color="#022c22" />
      ) : (
        <Text style={styles.text}>{title}</Text>
      )}
    </TouchableOpacity>
  );
};

const styles = StyleSheet.create({
  button: {
    backgroundColor: '#22c55e', // green-500
    paddingVertical: 12,
    borderRadius: 999,
    alignItems: 'center',
  },
  buttonDisabled: {
    backgroundColor: '#bbf7d0', // green-200
  },
  text: {
    color: '#022c22', // very dark teal
    fontSize: 16,
    fontWeight: '600',
  },
});
