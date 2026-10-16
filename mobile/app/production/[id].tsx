import { Image } from 'expo-image';
import { Link, router, Stack, useLocalSearchParams } from 'expo-router';
import * as WebBrowser from 'expo-web-browser';
import { Bookmark, Check, Clock, Landmark, Ticket } from 'lucide-react-native';
import { useState } from 'react';
import { Pressable, ScrollView, View } from 'react-native';

import { useProduction, useReviews, useWatchlist } from '@/src/api/hooks';
import type { Credit, Performance } from '@/src/api/types';
import { useAuth } from '@/src/auth';
import { LogSheet } from '@/src/components/LogSheet';
import { Poster } from '@/src/components/Poster';
import { ReviewCard } from '@/src/components/ReviewCard';
import { ShareButton } from '@/src/components/ShareButton';
import { RatingBadge, Stars } from '@/src/components/Stars';
import { ErrorState, Loading } from '@/src/components/States';
import { formatPrice, formatTime, guestLabel } from '@/src/format';
import { links } from '@/src/links';
import { Avatar, Button, colors, Section, Separator, Text } from '@/src/ui';

export default function ProductionScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const productionId = Number(id);
  const { user } = useAuth();
  const { data, isLoading, isError, error, refetch } = useProduction(productionId);
  const reviews = useReviews(productionId);
  const watchlist = useWatchlist(productionId);
  const [logging, setLogging] = useState(false);
  const [moreSynopsis, setMoreSynopsis] = useState(false);

  if (isLoading) return <Loading />;
  if (isError || !data) return <ErrorState onRetry={refetch} error={error} />;

  const { production: p, me } = data;
  const needAccount = () => router.push('/sign-in');

  const directors = p.credits.filter((c) => c.role_type === 'director');
  const cast = p.credits.filter((c) => c.role_type === 'actor');
  const crew = p.credits.filter((c) => c.role_type !== 'actor' && c.role_type !== 'director');
  const guest = guestLabel(p);
  const facts = [p.running_time_minutes && `${p.running_time_minutes} мин.`, p.age_advisory].filter(Boolean).join(' · ');

  return (
    <ScrollView className="flex-1 bg-background" contentContainerClassName="pb-12">
      <Stack.Screen
        options={{
          title: '',
          headerRight: () => <ShareButton text={[p.title, p.theatre?.name].filter(Boolean).join(' — ')} url={links.production(p.id)} />,
        }}
      />

      {/* Hero: the poster, blurred and dimmed behind the real one. */}
      <View className="overflow-hidden">
        {p.poster_url && (
          <>
            <Image source={p.poster_url} style={{ position: 'absolute', top: 0, left: 0, right: 0, bottom: 0 }} contentFit="cover" blurRadius={40} />
            <View className="absolute inset-0 bg-background/75" />
            <View className="absolute bottom-0 left-0 right-0 h-16 bg-background/60" />
          </>
        )}
        <View className="flex-row gap-4 px-4 pb-5 pt-2">
          <Poster uri={p.poster_url} width={118} radius={8} />
          <View className="flex-1 justify-end gap-1.5">
            <Text variant="title">{p.title}</Text>
            {p.author && <Text className="text-foreground/80">{p.author}</Text>}
            {directors.length > 0 && (
              <Text variant="muted">
                реж. <Text variant="small" className="font-body-semibold text-foreground">{directors.map((d) => d.person.name).join(', ')}</Text>
              </Text>
            )}
            {facts ? (
              <View className="flex-row items-center gap-1.5">
                <Clock size={13} color={colors.muted} />
                <Text variant="caption">{facts}</Text>
              </View>
            ) : null}
          </View>
        </View>
      </View>

      <View className="gap-4 px-4">
        {p.theatre && (
          <Link href={{ pathname: '/theatre/[id]', params: { id: p.theatre.id } }} asChild>
            <Pressable className="flex-row items-center gap-2 self-start">
              <Landmark size={15} color={colors.muted} />
              <Text variant="small" className="font-body-medium">
                {p.theatre.name}
              </Text>
            </Pressable>
          </Link>
        )}
        {guest && <Text variant="caption">{guest}</Text>}

        {p.rating_average !== null && (
          <View className="flex-row items-center gap-3 rounded-2xl border border-border bg-card px-4 py-3">
            <Text className="font-display text-[32px] leading-10 text-gold">{p.rating_average.toFixed(1).replace('.', ',')}</Text>
            <View className="gap-0.5">
              <Stars halfStars={Math.round(p.rating_average * 2)} size={14} />
              <Text variant="caption">{ratingsLabel(p.ratings_count)}</Text>
            </View>
          </View>
        )}

        <View className="flex-row gap-3">
          <Button className="flex-1" icon={Check} onPress={() => (user ? setLogging(true) : needAccount())}>
            {me?.seen_on.length ? 'Гледах пак' : 'Видях'}
          </Button>
          <Button
            className="flex-1"
            variant="secondary"
            icon={Bookmark}
            iconFilled={me?.on_watchlist}
            iconColor={me?.on_watchlist ? colors.gold : undefined}
            loading={watchlist.isPending}
            onPress={() => (user ? watchlist.mutate(!me?.on_watchlist) : needAccount())}
          >
            {me?.on_watchlist ? 'В списъка' : 'Искам да гледам'}
          </Button>
        </View>

        {me && (me.half_stars || me.seen_on.length > 0) ? (
          <View className="flex-row items-center gap-2">
            <Text variant="caption">Вие:</Text>
            <Stars halfStars={me.half_stars} size={13} />
            {me.seen_on.length > 0 && (
              <Text variant="caption">гледано {me.seen_on.map((d) => d.split('-').reverse().join('.')).join(', ')}</Text>
            )}
          </View>
        ) : null}

        {p.synopsis && (
          <Pressable onPress={() => setMoreSynopsis(!moreSynopsis)}>
            <Text className="text-[15px] leading-[23px] text-foreground/85" numberOfLines={moreSynopsis ? undefined : 5}>
              {p.synopsis}
            </Text>
            {!moreSynopsis && p.synopsis.length > 280 && (
              <Text variant="small" className="mt-1 font-body-semibold text-muted">
                Още
              </Text>
            )}
          </Pressable>
        )}
      </View>

      <LogSheet productionId={p.id} title={p.title} me={me} visible={logging} onDismiss={() => setLogging(false)} />

      {p.upcoming_performances.length > 0 && (
        <Section title="Предстоящи представления">
          <View className="mx-4 overflow-hidden rounded-2xl border border-border bg-card">
            {p.upcoming_performances.map((perf, i) => (
              <View key={perf.id}>
                {i > 0 && <Separator />}
                <PerformanceRow perf={perf} fallbackUrl={p.box_office_url} />
              </View>
            ))}
          </View>
        </Section>
      )}

      <CreditList title="Актьори" credits={cast} showCharacter />
      <CreditList title="Екип" credits={[...directors, ...crew]} showRole />

      <Section title={`Ревюта${reviews.data?.meta.total ? ` · ${reviews.data.meta.total}` : ''}`}>
        {reviews.data?.data.length ? (
          reviews.data.data.map((r, i) => (
            <View key={r.id}>
              {i > 0 && <Separator className="ml-16" />}
              <ReviewCard review={r} />
            </View>
          ))
        ) : (
          <Text variant="muted" className="px-4">
            Все още няма ревюта. Бъдете първи!
          </Text>
        )}
      </Section>
    </ScrollView>
  );
}

function PerformanceRow({ perf, fallbackUrl }: { perf: Performance; fallbackUrl: string | null }) {
  const url = perf.ticket_url ?? fallbackUrl;
  const date = new Date(perf.starts_at);
  const day = new Intl.DateTimeFormat('bg-BG', { day: 'numeric', timeZone: 'Europe/Sofia' }).format(date);
  const month = new Intl.DateTimeFormat('bg-BG', { month: 'short', timeZone: 'Europe/Sofia' }).format(date).replace('.', '');
  const weekday = new Intl.DateTimeFormat('bg-BG', { weekday: 'long', timeZone: 'Europe/Sofia' }).format(date);
  const price = formatPrice(perf.price_from, perf.price_to);

  return (
    <View className="flex-row items-center gap-3 px-4 py-3">
      <View className="w-11 items-center">
        <Text className="font-display text-[22px] leading-7">{day}</Text>
        <Text variant="caption" className="uppercase">
          {month}
        </Text>
      </View>
      <View className="flex-1">
        <Text className="font-body-semibold capitalize">
          {weekday}, {formatTime(perf.starts_at)}
        </Text>
        <Text variant="caption" numberOfLines={1}>
          {[perf.stage, price].filter(Boolean).join(' · ') || ' '}
        </Text>
      </View>
      {url && (
        <Button size="sm" variant="outline" icon={Ticket} onPress={() => WebBrowser.openBrowserAsync(url)}>
          Билети
        </Button>
      )}
    </View>
  );
}

function CreditList({ title, credits, showCharacter, showRole }: { title: string; credits: Credit[]; showCharacter?: boolean; showRole?: boolean }) {
  if (credits.length === 0) return null;

  return (
    <Section title={title}>
      <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerClassName="gap-4 px-4">
        {credits.map((c, i) => (
          <Link key={`${c.person.id}-${i}`} href={{ pathname: '/person/[id]', params: { id: c.person.id } }} asChild>
            <Pressable className="w-[84px] items-center gap-1.5 active:opacity-75">
              <Avatar name={c.person.name} size={64} />
              <Text variant="small" className="text-center font-body-medium leading-[17px]" numberOfLines={2}>
                {c.person.name}
              </Text>
              {(showCharacter ? c.character_name : showRole ? c.role_label : null) && (
                <Text variant="caption" className="text-center" numberOfLines={2}>
                  {showCharacter ? c.character_name : c.role_label}
                </Text>
              )}
            </Pressable>
          </Link>
        ))}
      </ScrollView>
    </Section>
  );
}

function ratingsLabel(n: number): string {
  return n === 1 ? '1 оценка' : `${n} оценки`;
}
