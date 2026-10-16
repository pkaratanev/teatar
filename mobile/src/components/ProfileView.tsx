import { Link } from 'expo-router';
import { Landmark, Sparkles } from 'lucide-react-native';
import type { ReactNode } from 'react';
import { ScrollView, View } from 'react-native';

import type { Profile } from '@/src/api/types';
import { Avatar, colors, ListItem, Section, Separator, Text } from '@/src/ui';
import { FollowButton } from './FollowButton';
import { ProductionRow } from './ProductionRow';
import { ReviewCard } from './ReviewCard';

/** Shared by the Profile tab (your own) and /user/[username] (someone else's). */
export function ProfileView({
  profile,
  actions,
  header,
  footer,
  topInset = 0,
}: {
  profile: Profile;
  /** Buttons next to the name, e.g. "Редактирай". */
  actions?: ReactNode;
  header?: ReactNode;
  footer?: ReactNode;
  topInset?: number;
}) {
  const { stats } = profile;

  return (
    <ScrollView className="flex-1 bg-background" contentContainerStyle={{ paddingBottom: 40, paddingTop: topInset }}>
      <View className="items-center gap-1 px-6 pt-6">
        <Avatar name={profile.name} size={88} />
        <Text variant="title" className="mt-3 text-center">
          {profile.name}
        </Text>
        <Text variant="muted">@{profile.username}</Text>
        {profile.bio && <Text className="mt-2 text-center text-[15px] leading-[22px] text-foreground/90">{profile.bio}</Text>}
        <View className="mt-3 flex-row gap-5">
          <Count value={profile.followers_count} label="последователи" />
          <Count value={profile.following_count} label="следва" />
        </View>
        <View className="mt-4 flex-row gap-2">
          {!profile.is_me && (
            <FollowButton
              target={{ username: profile.username }}
              state={{ is_following: profile.is_following, followers_count: profile.followers_count }}
            />
          )}
          {actions}
        </View>
      </View>

      <View className="mx-4 mt-6 flex-row rounded-2xl border border-border bg-card">
        <Tile value={stats.seen_this_year} label="тази година" />
        <View className="my-3 w-px bg-border" />
        <Tile value={stats.seen_total} label="гледани" />
        <View className="my-3 w-px bg-border" />
        <Tile value={stats.ratings_count} label="оценки" />
      </View>

      {stats.ratings_count > 0 && <Histogram counts={stats.ratings_histogram} />}

      {(stats.favourite_theatre || stats.most_seen_actor) && (
        <View className="pt-4">
          {stats.favourite_theatre && (
            <Link href={{ pathname: '/theatre/[id]', params: { id: stats.favourite_theatre.id } }} asChild>
              <ListItem
                icon={Landmark}
                title={stats.favourite_theatre.name}
                description={`Любим театър · ${stats.favourite_theatre.visits} посещения`}
                role="link"
              />
            </Link>
          )}
          {stats.most_seen_actor && (
            <Link href={{ pathname: '/person/[id]', params: { id: stats.most_seen_actor.id } }} asChild>
              <ListItem
                icon={Sparkles}
                title={stats.most_seen_actor.name}
                description={`Най-гледан актьор · ${stats.most_seen_actor.times} пъти`}
                role="link"
              />
            </Link>
          )}
        </View>
      )}

      {header}

      {profile.recent_diary.length > 0 && (
        <Section title="Последно гледани">
          {profile.recent_diary.map((e) => (
            <ProductionRow key={e.id} production={e.production} detail={e.seen_on.split('-').reverse().join('.')} />
          ))}
        </Section>
      )}

      {profile.recent_reviews.length > 0 && (
        <Section title="Ревюта">
          {profile.recent_reviews.map((r, i) => (
            <View key={r.id}>
              {i > 0 && <Separator className="ml-4" />}
              <ReviewCard review={r} showProduction />
            </View>
          ))}
        </Section>
      )}

      {footer}
    </ScrollView>
  );
}

function Count({ value, label }: { value: number; label: string }) {
  return (
    <Text variant="muted">
      <Text variant="small" className="font-body-semibold text-foreground">
        {value}
      </Text>{' '}
      {label}
    </Text>
  );
}

function Tile({ value, label }: { value: number; label: string }) {
  return (
    <View className="flex-1 items-center py-4">
      <Text className="font-display text-[26px] leading-8">{value}</Text>
      <Text variant="caption">{label}</Text>
    </View>
  );
}

/** Ten bars, ½★ … 5★, like Letterboxd's ratings spread. */
function Histogram({ counts }: { counts: number[] }) {
  const max = Math.max(1, ...counts);

  return (
    <Section title="Оценки">
      <View className="flex-row items-end gap-3 px-4" accessibilityLabel="Разпределение на оценките">
        <Text variant="caption" className="text-gold">
          ½★
        </Text>
        <View className="h-16 flex-1 flex-row items-end gap-1">
          {counts.map((n, i) => (
            <View
              key={i}
              className="flex-1 rounded-t"
              style={{ height: 3 + (n / max) * 61, backgroundColor: n ? colors.gold : colors.elevated, opacity: n ? 0.55 + (0.45 * n) / max : 1 }}
            />
          ))}
        </View>
        <Text variant="caption" className="text-gold">
          ★★★★★
        </Text>
      </View>
    </Section>
  );
}
