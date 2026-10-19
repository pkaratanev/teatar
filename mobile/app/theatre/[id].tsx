import { Stack, useLocalSearchParams } from 'expo-router';
import * as Linking from 'expo-linking';
import * as WebBrowser from 'expo-web-browser';
import { Globe, Landmark, MapPin, Navigation, Phone, Ticket } from 'lucide-react-native';
import { ScrollView, View } from 'react-native';

import { useTheatre } from '@/src/api/hooks';
import { ProductionRow } from '@/src/components/ProductionRow';
import { ShareButton } from '@/src/components/ShareButton';
import { ErrorState, Loading } from '@/src/components/States';
import { links } from '@/src/links';
import { hasLocation, openDirections, openInMaps } from '@/src/maps';
import { Button, colors, ListItem, Section, Text } from '@/src/ui';

/** "https://www.theatro.bg/" → "theatro.bg" */
const host = (url: string) => url.replace(/^https?:\/\/(www\.)?/, '').replace(/\/.*$/, '');

export default function TheatreScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { data: t, isLoading, isError, error, refetch } = useTheatre(Number(id));

  if (isLoading) return <Loading />;
  if (isError || !t) return <ErrorState onRetry={refetch} error={error} />;

  const located = hasLocation(t);
  const ticketsUrl = t.box_office_url ?? t.website;

  return (
    <ScrollView className="flex-1 bg-background" contentContainerClassName="pb-12">
      <Stack.Screen options={{ title: '', headerRight: () => <ShareButton text={t.name} url={links.theatre(t.id)} /> }} />
      <View className="gap-3 px-4 pb-2 pt-2">
        <View className="h-14 w-14 items-center justify-center rounded-2xl bg-card">
          <Landmark size={26} color={colors.primary} />
        </View>
        <Text variant="title">{t.name}</Text>
        <View className="mt-1 flex-row gap-3">
          {ticketsUrl && (
            <Button className="flex-1" icon={Ticket} onPress={() => WebBrowser.openBrowserAsync(ticketsUrl)}>
              Билети
            </Button>
          )}
          {located && (
            <Button className="flex-1" variant="secondary" icon={Navigation} onPress={() => openDirections(t)}>
              Как да стигна
            </Button>
          )}
        </View>
      </View>

      <View className="mt-2">
        {located && (
          <ListItem
            icon={MapPin}
            title={t.address ?? t.city}
            description={t.address ? `${t.city} · Отвори в карти` : 'Отвори в карти'}
            accessibilityRole="link"
            onPress={() => openInMaps(t)}
          />
        )}
        {t.phone && (
          <ListItem
            icon={Phone}
            title={t.phone}
            description="Каса"
            accessibilityRole="link"
            onPress={() => Linking.openURL(`tel:${t.phone!.replace(/[^\d+]/g, '')}`)}
          />
        )}
        {t.website && (
          <ListItem
            icon={Globe}
            title={host(t.website)}
            description="Сайт на театъра"
            accessibilityRole="link"
            onPress={() => WebBrowser.openBrowserAsync(t.website!)}
          />
        )}
      </View>

      <Section title={`На репертоар${t.productions?.length ? ` · ${t.productions.length}` : ''}`}>
        {(t.productions ?? []).map((p) => (
          <ProductionRow key={p.id} production={p} />
        ))}
      </Section>
    </ScrollView>
  );
}
