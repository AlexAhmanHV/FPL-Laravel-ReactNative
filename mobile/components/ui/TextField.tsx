import React from 'react';
import {
  View,
  Text,
  TextInput,
  TextInputProps,
  StyleSheet,
} from 'react-native';

interface TextFieldProps extends TextInputProps {
  label?: string;
  errorText?: string | null;
}

export const TextField: React.FC<TextFieldProps> = ({
  label,
  errorText,
  style,
  ...inputProps
}) => {
  return (
    <View style={styles.container}>
      {label && <Text style={styles.label}>{label}</Text>}
      <TextInput
        style={[styles.input, style]}
        placeholderTextColor="#9ca3af"
        {...inputProps}
      />
      {errorText ? <Text style={styles.error}>{errorText}</Text> : null}
    </View>
  );
};

const styles = StyleSheet.create({
  container: {
    marginBottom: 12,
  },
  label: {
    fontSize: 14,
    color: '#374151',
    marginBottom: 4,
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
    marginTop: 4,
    fontSize: 12,
    color: '#b91c1c',
  },
});
