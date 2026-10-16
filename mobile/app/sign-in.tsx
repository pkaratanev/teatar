import { router } from 'expo-router';
import { Drama } from 'lucide-react-native';
import { useState } from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, View } from 'react-native';

import { fieldErrors, useAuth } from '@/src/auth';
import { Button, colors, Field, Segmented, Text } from '@/src/ui';

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
    <KeyboardAvoidingView className="flex-1 bg-background" behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <ScrollView contentContainerClassName="gap-5 px-6 pb-10 pt-4" keyboardShouldPersistTaps="handled">
        <View className="items-center gap-3 pb-2">
          <View className="h-16 w-16 items-center justify-center rounded-full bg-card">
            <Drama size={30} color={colors.primary} />
          </View>
          <Text variant="display">Teatar</Text>
          <Text variant="muted" className="text-center">
            Оценявайте постановки, пишете ревюта и водете дневник на гледаното.
          </Text>
        </View>

        <Segmented
          value={mode}
          onChange={setMode}
          options={[
            { value: 'in', label: 'Вход' },
            { value: 'up', label: 'Регистрация' },
          ]}
        />

        {mode === 'up' && (
          <>
            <Field label="Име" value={name} onChangeText={setName} autoComplete="name" error={errors.name} />
            <Field
              label="Потребителско име"
              value={username}
              onChangeText={setUsername}
              autoCapitalize="none"
              autoCorrect={false}
              error={errors.username}
              hint="Латински букви, цифри, точка и долна черта."
            />
          </>
        )}

        <Field
          label="Имейл"
          value={email}
          onChangeText={setEmail}
          autoCapitalize="none"
          autoComplete="email"
          keyboardType="email-address"
          error={errors.email}
        />
        <Field
          label="Парола"
          value={password}
          onChangeText={setPassword}
          secureTextEntry
          autoComplete={mode === 'in' ? 'current-password' : 'new-password'}
          error={errors.password}
          onSubmitEditing={submit}
        />

        {failed && (
          <Text variant="caption" className="text-destructive">
            Нещо се обърка. Проверете връзката и опитайте пак.
          </Text>
        )}

        <Button onPress={submit} loading={busy} className="mt-1">
          {mode === 'in' ? 'Вход' : 'Създай профил'}
        </Button>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}
