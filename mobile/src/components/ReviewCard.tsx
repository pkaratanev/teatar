import { Link, router } from 'expo-router';
import { EyeOff, Flag, Heart, MoreHorizontal } from 'lucide-react-native';
import { useEffect, useState } from 'react';
import { Pressable, View } from 'react-native';

import { useLike, useReport } from '@/src/api/hooks';
import type { ReportReason, Review } from '@/src/api/types';
import { useAuth } from '@/src/auth';
import { Avatar, colors, ListItem, Sheet, Text } from '@/src/ui';
import { Poster } from './Poster';
import { Stars } from './Stars';

const REASONS: { value: ReportReason; label: string }[] = [
  { value: 'spoiler', label: 'Спойлер без отбелязване' },
  { value: 'abuse', label: 'Обидно съдържание' },
  { value: 'spam', label: 'Спам' },
  { value: 'other', label: 'Друго' },
];

/** One review: author, stars, text. Spoilers stay hidden until tapped; others' reviews can be reported. */
export function ReviewCard({
  review,
  showProduction = false,
  showAuthor = false,
}: {
  review: Review;
  showProduction?: boolean;
  /** With showProduction: also name the author (the feed). */
  showAuthor?: boolean;
}) {
  const { user } = useAuth();
  const report = useReport();
  const like = useLike(review);
  const liked = like.data?.data.liked_by_me ?? review.liked_by_me;
  const likes = like.data?.data.likes_count ?? review.likes_count;
  const [revealed, setRevealed] = useState(!review.has_spoilers || review.is_mine);
  const [menu, setMenu] = useState(false);
  const [thanks, setThanks] = useState(false);

  useEffect(() => {
    if (!thanks) return;
    const t = setTimeout(() => setThanks(false), 3000);
    return () => clearTimeout(t);
  }, [thanks]);

  const author = review.user;
  const production = showProduction ? review.production : null;

  return (
    <View className="flex-row gap-3 px-4 py-4">
      {production ? (
        <Link href={{ pathname: '/production/[id]', params: { id: production.id } }} asChild>
          <Pressable>
            <Poster uri={production.poster_url} title={production.title} width={44} radius={4} />
          </Pressable>
        </Link>
      ) : author ? (
        <Link href={{ pathname: '/user/[username]', params: { username: author.username } }} asChild>
          <Pressable>
            <Avatar name={author.name} size={36} />
          </Pressable>
        </Link>
      ) : null}

      <View className="flex-1 gap-1.5">
        <View className="flex-row items-start gap-2">
          <View className="flex-1 gap-0.5">
            {production ? (
              <Link href={{ pathname: '/production/[id]', params: { id: production.id } }}>
                <Text className="font-body-semibold">{production.title}</Text>
              </Link>
            ) : author ? (
              <Link href={{ pathname: '/user/[username]', params: { username: author.username } }}>
                <Text className="font-body-semibold">{author.name}</Text>
              </Link>
            ) : null}
            <View className="flex-row items-center gap-2">
              <Stars halfStars={review.half_stars ?? null} size={13} />
              {production && showAuthor && author && (
                <Link href={{ pathname: '/user/[username]', params: { username: author.username } }}>
                  <Text variant="caption">от {author.name}</Text>
                </Link>
              )}
            </View>
          </View>
          {user && !review.is_mine && (
            <Pressable hitSlop={10} onPress={() => setMenu(true)} accessibilityLabel="Още" className="pt-0.5">
              <MoreHorizontal size={18} color={colors.faint} />
            </Pressable>
          )}
        </View>

        {revealed ? (
          <Text className="text-[15px] leading-[22px] text-foreground/90">{review.body}</Text>
        ) : (
          <Pressable
            onPress={() => setRevealed(true)}
            role="button"
            className="flex-row items-center gap-2 self-start rounded-lg bg-elevated px-3 py-2"
          >
            <EyeOff size={14} color={colors.muted} />
            <Text variant="muted">Съдържа спойлери. Докоснете, за да прочетете.</Text>
          </Pressable>
        )}

        <View className="flex-row items-center gap-4 pt-0.5">
          <Pressable
            hitSlop={8}
            className="flex-row items-center gap-1.5"
            disabled={review.is_mine}
            onPress={() => (user ? like.mutate(!liked) : router.push('/sign-in'))}
            accessibilityLabel={liked ? 'Махни харесването' : 'Харесай'}
          >
            <Heart size={15} color={liked ? colors.primary : colors.faint} fill={liked ? colors.primary : 'transparent'} />
            <Text variant="caption" className={liked ? 'text-primary' : undefined}>
              {likes > 0 ? String(likes) : review.is_mine ? '' : 'Харесай'}
            </Text>
          </Pressable>
          {thanks && <Text variant="caption">Благодарим! Ще прегледаме ревюто.</Text>}
        </View>
      </View>

      <Sheet visible={menu} onClose={() => setMenu(false)} title="Докладвай ревюто">
        <View className="-mx-5 pb-2">
          {REASONS.map((r) => (
            <ListItem
              key={r.value}
              icon={Flag}
              title={r.label}
              right={null}
              onPress={() => {
                setMenu(false);
                report.mutate({ reviewId: review.id, reason: r.value }, { onSuccess: () => setThanks(true) });
              }}
            />
          ))}
        </View>
      </Sheet>
    </View>
  );
}
