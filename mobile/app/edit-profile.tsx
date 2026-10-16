import { router } from 'expo-router';
import { useState } from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet } from 'react-native';
import { Button, HelperText, Text, TextInput } from 'react-native-paper';

import { fieldErrors, useAuth } from '@/src/auth';

const BIO_MAX = 500;

/** Edit display name and bio. Opened from the Profile tab. */
export default function EditProfileScreen() {
  const { user, updateProfile } = useAuth();
  const [name, setName] = useState(user?.name ?? '');
  const [bio, setBio] = useState(user?.bio ?? '');
  const [busy, setBusy] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [failed, setFailed] = useState(false);

  if (!user) {
    return <Text style={styles.content}>Влезте, за да редактирате профила си.</Text>;
  }

  const unchanged = name.trim() === user.name && (bio.trim() || null) === (user.bio ?? null);

  const save = async () => {
    setBusy(true);
    setErrors({});
    setFailed(false);
    try {
      await updateProfile({ name: name.trim(), bio: bio.trim() || null });
      if (router.canGoBack()) router.back();
      else router.replace('/profile');
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
        <TextInput mode="outlined" label="Име" value={name} onChangeText={setName} autoComplete="name" maxLength={255} />
        <HelperText type="error" visible={!!errors.name}>
          {errors.name}
        </HelperText>

        <TextInput
          mode="outlined"
          label="За мен"
          value={bio}
          onChangeText={setBio}
          multiline
          numberOfLines={5}
          maxLength={BIO_MAX}
          style={styles.bio}
        />
        <HelperText type={errors.bio ? 'error' : 'info'} visible>
          {errors.bio ?? `${bio.length}/${BIO_MAX}`}
        </HelperText>

        <Text variant="bodySmall" style={styles.muted}>
          Потребителското име (@{user.username}) не може да се променя.
        </Text>

        {failed && (
          <HelperText type="error" visible>
            Не успяхме да запазим промените. Опитайте пак.
          </HelperText>
        )}

        <Button mode="contained" onPress={save} loading={busy} disabled={busy || unchanged || !name.trim()} style={styles.save}>
          Запази
        </Button>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  flex: { flex: 1 },
  content: { padding: 16 },
  bio: { minHeight: 120 },
  muted: { opacity: 0.6 },
  save: { marginTop: 16 },
});
