import { Stack, useLocalSearchParams } from 'expo-router';
import { ScrollView, View } from 'react-native';

import { usePerson } from '@/src/api/hooks';
import type { PersonCredit } from '@/src/api/types';
import { FollowButton } from '@/src/components/FollowButton';
import { ProductionRow } from '@/src/components/ProductionRow';
import { ShareButton } from '@/src/components/ShareButton';
import { ErrorState, Loading } from '@/src/components/States';
import { links } from '@/src/links';
import { Avatar, Section, Text } from '@/src/ui';

export default function PersonScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { data, isLoading, isError, error, refetch } = usePerson(Number(id));

  if (isLoading) return <Loading />;
  if (isError || !data) return <ErrorState onRetry={refetch} error={error} />;

  const { person, social } = data;
  const count = person.current.length + person.past.length;

  return (
    <ScrollView className="flex-1 bg-background" contentContainerClassName="pb-12">
      <Stack.Screen options={{ title: '', headerRight: () => <ShareButton text={person.name} url={links.person(person.id)} /> }} />
      <View className="items-center gap-1 px-6 pt-2">
        <Avatar name={person.name} uri={person.headshot_url} size={104} />
        <Text variant="title" className="mt-3 text-center">
          {person.name}
        </Text>
        <Text variant="muted">
          {count === 1 ? '1 постановка' : `${count} постановки`}
          {social.followers_count > 0 ? ` · ${social.followers_count} последователи` : ''}
        </Text>
        <View className="mt-3">
          <FollowButton target={{ personId: person.id }} state={social} />
        </View>
      </View>
      {person.bio && <Text className="px-4 pt-5 text-[15px] leading-[23px] text-foreground/85">{person.bio}</Text>}
      <Credits title="Сега на сцена" credits={person.current} />
      <Credits title="Минали постановки" credits={person.past} />
    </ScrollView>
  );
}

function Credits({ title, credits }: { title: string; credits: PersonCredit[] }) {
  if (credits.length === 0) return null;

  return (
    <Section title={title}>
      {credits.map((c, i) => (
        <ProductionRow
          key={`${c.production.id}-${i}`}
          production={c.production}
          detail={c.character_name ? `${c.role_label} · ${c.character_name}` : c.role_label}
        />
      ))}
    </Section>
  );
}
