import { Stack, useLocalSearchParams } from 'expo-router';
import * as Linking from 'expo-linking';
import * as WebBrowser from 'expo-web-browser';
import { Landmark, MapPin, Ticket } from 'lucide-react-native';
import { ScrollView, View } from 'react-native';

import { useTheatre } from '@/src/api/hooks';
import { ProductionRow } from '@/src/components/ProductionRow';
import { ShareButton } from '@/src/components/ShareButton';
import { ErrorState, Loading } from '@/src/components/States';
import { links } from '@/src/links';
import { Button, colors, Section, Text } from '@/src/ui';

export default function TheatreScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { data: t, isLoading, isError, error, refetch } = useTheatre(Number(id));

  if (isLoading) return <Loading />;
  if (isError || !t) return <ErrorState onRetry={refetch} error={error} />;

  const mapUrl =
    t.lat !== null && t.lng !== null
      ? `https://maps.google.com/?q=${t.lat},${t.lng}`
      : t.address
        ? `https://maps.google.com/?q=${encodeURIComponent(`${t.address}, ${t.city}`)}`
        : null;

  return (
    <ScrollView className="flex-1 bg-background" contentContainerClassName="pb-12">
      <Stack.Screen options={{ title: '', headerRight: () => <ShareButton text={t.name} url={links.theatre(t.id)} /> }} />
      <View className="gap-3 px-4 pb-2 pt-2">
        <View className="h-14 w-14 items-center justify-center rounded-2xl bg-card">
          <Landmark size={26} color={colors.primary} />
        </View>
        <Text variant="title">{t.name}</Text>
        {(t.address || t.city) && (
          <View className="flex-row items-center gap-1.5">
            <MapPin size={14} color={colors.muted} />
            <Text variant="muted">{[t.address, t.city].filter(Boolean).join(', ')}</Text>
          </View>
        )}
        <View className="mt-1 flex-row gap-3">
          {t.box_office_url && (
            <Button className="flex-1" icon={Ticket} onPress={() => WebBrowser.openBrowserAsync(t.box_office_url!)}>
              Билети
            </Button>
          )}
          {mapUrl && (
            <Button className="flex-1" variant="secondary" icon={MapPin} onPress={() => Linking.openURL(mapUrl)}>
              Карта
            </Button>
          )}
        </View>
      </View>
      <Section title={`На репертоар${t.productions?.length ? ` · ${t.productions.length}` : ''}`}>
        {(t.productions ?? []).map((p) => (
          <ProductionRow key={p.id} production={p} />
        ))}
      </Section>
    </ScrollView>
  );
}
