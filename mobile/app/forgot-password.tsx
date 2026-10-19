import { router, useLocalSearchParams } from 'expo-router';
import { useState } from 'react';
import { KeyboardAvoidingView, Platform, ScrollView } from 'react-native';

import { fieldErrors, useAuth } from '@/src/auth';
import { Button, Field, Text } from '@/src/ui';

/**
 * Forgotten password in two steps: the email gets a 6-digit code, then the code
 * and a new password sign you in. Opened from the sign-in screen.
 */
export default function ForgotPasswordScreen() {
  const { sendResetCode, resetPassword } = useAuth();
  const params = useLocalSearchParams<{ email?: string }>();
  const [step, setStep] = useState<'email' | 'code'>('email');
  const [email, setEmail] = useState(params.email ?? '');
  const [code, setCode] = useState('');
  const [password, setPassword] = useState('');
  const [busy, setBusy] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [failed, setFailed] = useState(false);

  const run = async (action: () => Promise<void>) => {
    setBusy(true);
    setErrors({});
    setFailed(false);
    try {
      await action();
    } catch (e) {
      const fields = fieldErrors(e);
      setErrors(fields);
      setFailed(Object.keys(fields).length === 0);
    } finally {
      setBusy(false);
    }
  };

  const send = () =>
    run(async () => {
      await sendResetCode(email.trim());
      setStep('code');
    });

  const reset = () =>
    run(async () => {
      await resetPassword({ email: email.trim(), code: code.trim(), password });
      // Close this screen and the sign-in screen under it.
      if (router.canDismiss()) router.dismiss(2);
      else router.replace('/');
    });

  return (
    <KeyboardAvoidingView className="flex-1 bg-background" behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <ScrollView contentContainerClassName="gap-5 px-6 pb-10 pt-4" keyboardShouldPersistTaps="handled">
        {step === 'email' ? (
          <>
            <Text variant="muted">Въведете имейла на профила си и ще ви изпратим код за нова парола.</Text>
            <Field
              label="Имейл"
              value={email}
              onChangeText={setEmail}
              autoCapitalize="none"
              autoComplete="email"
              keyboardType="email-address"
              error={errors.email}
              onSubmitEditing={send}
            />
            {failed && <ErrorLine />}
            <Button onPress={send} loading={busy}>
              Изпрати код
            </Button>
          </>
        ) : (
          <>
            <Text variant="muted">
              Ако има профил с {email.trim()}, изпратихме код до него. Писмото може да отнеме минута; проверете и папката
              за спам.
            </Text>
            <Field
              label="Код от писмото"
              value={code}
              onChangeText={(v) => setCode(v.replace(/\D/g, '').slice(0, 6))}
              keyboardType="number-pad"
              autoComplete="one-time-code"
              textContentType="oneTimeCode"
              maxLength={6}
              error={errors.code}
            />
            <Field
              label="Нова парола"
              value={password}
              onChangeText={setPassword}
              secureTextEntry
              autoComplete="new-password"
              hint="Поне 8 знака."
              error={errors.password}
              onSubmitEditing={reset}
            />
            {failed && <ErrorLine />}
            <Button onPress={reset} loading={busy} disabled={code.length !== 6 || password.length === 0}>
              Запази паролата
            </Button>
            <Button variant="ghost" size="sm" onPress={send} disabled={busy}>
              Изпрати нов код
            </Button>
          </>
        )}
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

function ErrorLine() {
  return (
    <Text variant="caption" className="text-destructive">
      Нещо се обърка. Проверете връзката и опитайте пак.
    </Text>
  );
}
