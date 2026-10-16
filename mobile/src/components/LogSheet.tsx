import { useEffect, useState } from 'react';
import { ScrollView, Switch, View } from 'react-native';

import { fieldErrors } from '@/src/auth';
import { useLogVisit } from '@/src/api/hooks';
import type { MyProductionState } from '@/src/api/types';
import { Button, colors, Field, Input, Segmented, Sheet, Text } from '@/src/ui';
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
  const failed = log.isError && !Object.keys(errors).length;

  const save = () =>
    log.mutate(
      { seen_on: seenOn, half_stars: halfStars, review: review.trim() || null, has_spoilers: spoilers },
      { onSuccess: onDismiss },
    );

  return (
    <Sheet visible={visible} onClose={onDismiss} title={title}>
      <ScrollView keyboardShouldPersistTaps="handled" contentContainerClassName="gap-5 pb-2">
        <View className="gap-2">
          <Text variant="overline">Кога го гледахте?</Text>
          <Segmented
            value={when}
            onChange={setWhen}
            options={[
              { value: 'today', label: 'Днес' },
              { value: 'yesterday', label: 'Вчера' },
              { value: 'other', label: 'Друга дата' },
            ]}
          />
          {when === 'other' && (
            <Field
              label="Дата (ГГГГ-ММ-ДД)"
              placeholder="2026-09-21"
              value={otherDate}
              onChangeText={setOtherDate}
              keyboardType="numbers-and-punctuation"
              error={errors.seen_on}
            />
          )}
          {when !== 'other' && errors.seen_on && (
            <Text variant="caption" className="text-destructive">
              {errors.seen_on}
            </Text>
          )}
        </View>

        <View className="items-center gap-2">
          <Text variant="overline">Оценка</Text>
          <StarInput value={halfStars} onChange={setHalfStars} />
          <Text variant="caption">{halfStars ? `${String(halfStars / 2).replace('.', ',')} от 5` : 'Докоснете звезда; половин звезда е отляво'}</Text>
        </View>

        <View className="gap-2">
          <Input placeholder="Ревю (по желание)" value={review} onChangeText={setReview} multiline maxLength={5000} invalid={!!errors.review} />
          {errors.review && (
            <Text variant="caption" className="text-destructive">
              {errors.review}
            </Text>
          )}
          <View className="flex-row items-center justify-between">
            <Text variant="small">Съдържа спойлери</Text>
            <Switch
              value={spoilers}
              onValueChange={setSpoilers}
              trackColor={{ false: colors.elevated, true: colors.primary }}
              thumbColor={colors.foreground}
            />
          </View>
        </View>

        {failed && (
          <Text variant="caption" className="text-destructive">
            Не успяхме да запазим. Опитайте пак.
          </Text>
        )}

        <View className="flex-row gap-3">
          <Button variant="secondary" className="flex-1" onPress={onDismiss}>
            Отказ
          </Button>
          <Button className="flex-1" onPress={save} loading={log.isPending} disabled={!seenOn}>
            Запази
          </Button>
        </View>
      </ScrollView>
    </Sheet>
  );
}

/** Today (+offset days) as YYYY-MM-DD in Sofia time. */
function sofiaDate(offsetDays: number): string {
  const d = new Date(Date.now() + offsetDays * 86_400_000);
  return new Intl.DateTimeFormat('en-CA', { timeZone: 'Europe/Sofia' }).format(d);
}
