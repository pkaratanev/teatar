import { router } from 'expo-router';
import { useState } from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet } from 'react-native';
import { Button, HelperText, SegmentedButtons, Text, TextInput } from 'react-native-paper';

import { fieldErrors, useAuth } from '@/src/auth';

/** Sign in or create an account. Apple and Google sign-in come next (STATUS). */
export default function SignInScreen() {
  const { signIn, signUp } = useAuth();
  const [mode, setMode] = useState<'in' | 'up'>('in');
  const [name, setName] = useState('');
  const [username, setUsername] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [busy, setBusy] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [failed, setFailed] = useState(false);

  const submit = async () => {
    setBusy(true);
    setErrors({});
    setFailed(false);
    try {
      if (mode === 'in') {
        await signIn({ email: email.trim(), password });
      } else {
        await signUp({ name: name.trim(), username: username.trim().toLowerCase(), email: email.trim(), password });
      }
      if (router.canGoBack()) router.back();
      else router.replace('/');
    } catch (e) {
      const fields = fieldErrors(e);
      setErrors(fields);
      setFailed(Object.keys(fields).length === 0);
    } finally {
      setBusy(false);
    }
  };

  return (
    <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
        <Text variant="bodyLarge" style={styles.lead}>
          Влезте, за да оценявате, пишете ревюта и водите дневник на гледаното.
        </Text>

        <SegmentedButtons
          value={mode}
          onValueChange={(v) => setMode(v as 'in' | 'up')}
          buttons={[
            { value: 'in', label: 'Вход' },
            { value: 'up', label: 'Регистрация' },
          ]}
        />

        {mode === 'up' && (
          <>
            <TextInput mode="outlined" label="Име" value={name} onChangeText={setName} autoComplete="name" style={styles.field} />
            <HelperText type="error" visible={!!errors.name}>
              {errors.name}
            </HelperText>
            <TextInput
              mode="outlined"
              label="Потребителско име"
              value={username}
              onChangeText={setUsername}
              autoCapitalize="none"
              autoCorrect={false}
            />
            <HelperText type={errors.username ? 'error' : 'info'} visible>
              {errors.username ?? 'Латински букви, цифри, точка и долна черта.'}
            </HelperText>
          </>
        )}

        <TextInput
          mode="outlined"
          label="Имейл"
          value={email}
          onChangeText={setEmail}
          autoCapitalize="none"
          autoComplete="email"
          keyboardType="email-address"
          style={mode === 'in' ? styles.field : undefined}
        />
        <HelperText type="error" visible={!!errors.email}>
          {errors.email}
        </HelperText>

        <TextInput
          mode="outlined"
          label="Парола"
          value={password}
          onChangeText={setPassword}
          secureTextEntry
          autoComplete={mode === 'in' ? 'current-password' : 'new-password'}
        />
        <HelperText type="error" visible={!!errors.password || failed}>
          {errors.password ?? 'Нещо се обърка. Проверете връзката и опитайте пак.'}
        </HelperText>

        <Button mode="contained" onPress={submit} loading={busy} disabled={busy}>
          {mode === 'in' ? 'Вход' : 'Създай профил'}
        </Button>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1 },
  content: { padding: 20, gap: 4 },
  lead: { marginBottom: 16 },
  field: { marginTop: 16 },
});
