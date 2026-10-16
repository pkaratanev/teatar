import { router } from 'expo-router';
import { useEffect, useRef, useState } from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, View } from 'react-native';

import { fieldErrors, useAuth } from '@/src/auth';
import { Avatar, Button, Field, Text } from '@/src/ui';

const BIO_MAX = 500;

/** Edit display name and bio. Opened from the Profile tab. */
export default function EditProfileScreen() {
  const { user, updateProfile } = useAuth();
  const [name, setName] = useState(user?.name ?? '');
  const [bio, setBio] = useState(user?.bio ?? '');
  const [busy, setBusy] = useState(false);
  const [errors, setErrors] = useState<Record<string, string>>({});
  const [failed, setFailed] = useState(false);
  const filled = useRef(!!user);

  useEffect(() => {
    if (user && !filled.current) {
      filled.current = true;
      setName(user.name);
      setBio(user.bio ?? '');
    }
  }, [user]);

  if (!user) {
    return (
      <View className="flex-1 bg-background p-6">
        <Text variant="muted">Влезте, за да редактирате профила си.</Text>
      </View>
    );
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
    <KeyboardAvoidingView className="flex-1 bg-background" behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <ScrollView contentContainerClassName="gap-5 p-6" keyboardShouldPersistTaps="handled">
        <View className="items-center gap-1">
          <Avatar name={name || user.name} size={72} />
          <Text variant="muted" className="mt-2">
            @{user.username}
          </Text>
        </View>

        <Field label="Име" value={name} onChangeText={setName} autoComplete="name" maxLength={255} error={errors.name} />
        <Field
          label="За мен"
          value={bio}
          onChangeText={setBio}
          multiline
          maxLength={BIO_MAX}
          error={errors.bio}
          hint={`${bio.length}/${BIO_MAX}`}
          placeholder="Какво обичате да гледате?"
        />

        <Text variant="caption">Потребителското име не може да се променя.</Text>

        {failed && (
          <Text variant="caption" className="text-destructive">
            Не успяхме да запазим промените. Опитайте пак.
          </Text>
        )}

        <Button onPress={save} loading={busy} disabled={unchanged || !name.trim()}>
          Запази
        </Button>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}
