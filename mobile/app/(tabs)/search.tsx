import { Link } from 'expo-router';
import { Landmark, Search as SearchIcon, X } from 'lucide-react-native';
import { useState } from 'react';
import { Pressable, ScrollView, View } from 'react-native';

import { useSearch } from '@/src/api/hooks';
import { ProductionRow } from '@/src/components/ProductionRow';
import { ScreenHeader } from '@/src/components/ScreenHeader';
import { Avatar, colors, Input, ListItem, Section, Text } from '@/src/ui';

export default function SearchScreen() {
  const [q, setQ] = useState('');
  const { data, isFetching } = useSearch(q);
  const nothing = data && !data.productions.length && !data.people.length && !data.theatres.length;

  return (
    <View className="flex-1 bg-background">
      <ScreenHeader title="Търсене">
        <View className="mt-4 justify-center">
          <Input
            className="rounded-full pl-11 pr-11"
            placeholder="Постановка, актьор или театър"
            value={q}
            onChangeText={setQ}
            autoCorrect={false}
            returnKeyType="search"
          />
          <View className="absolute left-4" pointerEvents="none">
            <SearchIcon size={18} color={colors.muted} />
          </View>
          {q.length > 0 && (
            <Pressable className="absolute right-3 p-1" onPress={() => setQ('')} accessibilityLabel="Изчисти">
              <X size={18} color={colors.muted} />
            </Pressable>
          )}
        </View>
      </ScreenHeader>
      <ScrollView keyboardShouldPersistTaps="handled" contentContainerClassName="pb-8">
        {!q.trim() && (
          <Text variant="muted" className="px-4 pt-6 text-center">
            Търсете по заглавие, име на актьор или режисьор, или театър.
          </Text>
        )}
        {nothing && !isFetching && (
          <Text variant="muted" className="px-4 pt-6 text-center">
            Нищо не намерихме за „{q.trim()}“.
          </Text>
        )}
        {data && data.productions.length > 0 && (
          <Section title="Постановки" className="pt-2">
            {data.productions.map((p) => (
              <ProductionRow key={`p${p.id}`} production={p} />
            ))}
          </Section>
        )}
        {data && data.people.length > 0 && (
          <Section title="Хора">
            {data.people.map((person) => (
              <Link key={person.id} href={{ pathname: '/person/[id]', params: { id: person.id } }} asChild>
                <ListItem title={person.name} left={<Avatar name={person.name} size={40} />} />
              </Link>
            ))}
          </Section>
        )}
        {data && data.theatres.length > 0 && (
          <Section title="Театри">
            {data.theatres.map((t) => (
              <Link key={t.id} href={{ pathname: '/theatre/[id]', params: { id: t.id } }} asChild>
                <ListItem icon={Landmark} title={t.name} />
              </Link>
            ))}
          </Section>
        )}
      </ScrollView>
    </View>
  );
}
