import { useEffect, useState } from 'react';
import { KeyboardAvoidingView, Platform, ScrollView, StyleSheet, View } from 'react-native';
import { Button, HelperText, Modal, Portal, SegmentedButtons, Switch, Text, TextInput, useTheme } from 'react-native-paper';

import { fieldErrors } from '@/src/auth';
import { useLogVisit } from '@/src/api/hooks';
import type { MyProductionState } from '@/src/api/types';
import { StarInput } from './Stars';

type Props = {
  productionId: number;
  title: string;
  me: MyProductionState | null;
  visible: boolean;
  onDismiss: () => void;
};

/** "Видях" — date seen, ½–5 stars, optional review with a spoiler switch, saved in one request. */
export function LogSheet({ productionId, title, me, visible, onDismiss }: Props) {
  const theme = useTheme();
  const log = useLogVisit(productionId);
  const [when, setWhen] = useState<'today' | 'yesterday' | 'other'>('today');
  const [otherDate, setOtherDate] = useState('');
  const [halfStars, setHalfStars] = useState<number | null>(me?.half_stars ?? null);
  const [review, setReview] = useState(me?.review?.body ?? '');
  const [spoilers, setSpoilers] = useState(me?.review?.has_spoilers ?? false);

  // Start from the user's current rating and review each time the sheet opens.
  useEffect(() => {
    if (visible) {
      setHalfStars(me?.half_stars ?? null);
      setReview(me?.review?.body ?? '');
      setSpoilers(me?.review?.has_spoilers ?? false);
      log.reset();
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [visible]);

  const seenOn = when === 'today' ? sofiaDate(0) : when === 'yesterday' ? sofiaDate(-1) : otherDate.trim();
  const errors = fieldErrors(log.error);

  const save = () =>
    log.mutate(
      { seen_on: seenOn, half_stars: halfStars, review: review.trim() || null, has_spoilers: spoilers },
      { onSuccess: onDismiss },
    );

  return (
    <Portal>
      <Modal visible={visible} onDismiss={onDismiss} contentContainerStyle={[styles.sheet, { backgroundColor: theme.colors.surface }]}>
        <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
          <ScrollView keyboardShouldPersistTaps="handled">
            <Text variant="titleLarge" numberOfLines={2}>
              {title}
            </Text>

            <Text variant="labelLarge" style={styles.label}>
              Кога го гледахте?
            </Text>
            <SegmentedButtons
              value={when}
              onValueChange={(v) => setWhen(v as typeof when)}
              buttons={[
                { value: 'today', label: 'Днес' },
                { value: 'yesterday', label: 'Вчера' },
                { value: 'other', label: 'Друга дата' },
              ]}
            />
            {when === 'other' && (
              <TextInput
                mode="outlined"
                style={styles.gap}
                label="Дата (ГГГГ-ММ-ДД)"
                placeholder="2026-09-21"
                value={otherDate}
                onChangeText={setOtherDate}
                keyboardType="numbers-and-punctuation"
              />
            )}
            <HelperText type="error" visible={!!errors.seen_on}>
              {errors.seen_on}
            </HelperText>

            <Text variant="labelLarge" style={styles.label}>
              Оценка
            </Text>
            <View style={styles.center}>
              <StarInput value={halfStars} onChange={setHalfStars} />
            </View>

            <TextInput
              mode="outlined"
              style={styles.gap}
              label="Ревю (по желание)"
              value={review}
              onChangeText={setReview}
              multiline
              numberOfLines={4}
              maxLength={5000}
            />
            <View style={styles.switchRow}>
              <Text>Съдържа спойлери</Text>
              <Switch value={spoilers} onValueChange={setSpoilers} />
            </View>
            <HelperText type="error" visible={!!errors.review || (log.isError && !Object.keys(errors).length)}>
              {errors.review ?? 'Не успяхме да запазим. Опитайте пак.'}
            </HelperText>

            <View style={styles.actions}>
              <Button onPress={onDismiss}>Отказ</Button>
              <Button mode="contained" onPress={save} loading={log.isPending} disabled={log.isPending || !seenOn}>
                Запази
              </Button>
            </View>
          </ScrollView>
        </KeyboardAvoidingView>
      </Modal>
    </Portal>
  );
}

/** Today (+offset days) as YYYY-MM-DD in Sofia time. */
function sofiaDate(offsetDays: number): string {
  const d = new Date(Date.now() + offsetDays * 86_400_000);
  return new Intl.DateTimeFormat('en-CA', { timeZone: 'Europe/Sofia' }).format(d);
}

const styles = StyleSheet.create({
  sheet: { margin: 16, padding: 20, borderRadius: 16, maxHeight: '90%' },
  label: { marginTop: 16, marginBottom: 8 },
  gap: { marginTop: 12 },
  center: { alignItems: 'center' },
  switchRow: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginTop: 8 },
  actions: { flexDirection: 'row', justifyContent: 'flex-end', gap: 8, marginTop: 8 },
});
