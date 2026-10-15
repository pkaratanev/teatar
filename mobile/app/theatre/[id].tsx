import { Stack, useLocalSearchParams } from 'expo-router';
import * as Linking from 'expo-linking';
import * as WebBrowser from 'expo-web-browser';
import { ScrollView, StyleSheet, View } from 'react-native';
import { Button, List, Text } from 'react-native-paper';

import { useTheatre } from '@/src/api/hooks';
import { ProductionRow } from '@/src/components/ProductionRow';
import { ErrorState, Loading } from '@/src/components/States';

export default function TheatreScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { data: t, isLoading, isError, refetch } = useTheatre(Number(id));

  if (isLoading) return <Loading />;
  if (isError || !t) return <ErrorState onRetry={refetch} />;

  const mapUrl =
    t.lat !== null && t.lng !== null
      ? `https://maps.google.com/?q=${t.lat},${t.lng}`
      : t.address
        ? `https://maps.google.com/?q=${encodeURIComponent(`${t.address}, ${t.city}`)}`
        : null;

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <Stack.Screen options={{ title: t.name }} />
      <View style={styles.header}>
        <Text variant="headlineSmall">{t.name}</Text>
        {t.address && <Text style={styles.muted}>{t.address}</Text>}
        <View style={styles.actions}>
          {mapUrl && (
            <Button mode="outlined" icon="map-marker" onPress={() => Linking.openURL(mapUrl)}>
              Карта
            </Button>
          )}
          {t.box_office_url && (
            <Button mode="contained" icon="ticket" onPress={() => WebBrowser.openBrowserAsync(t.box_office_url!)}>
              Билети
            </Button>
          )}
        </View>
      </View>
      <List.Section title="На репертоар">
        {(t.productions ?? []).map((p) => (
          <ProductionRow key={p.id} production={p} />
        ))}
      </List.Section>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  content: { paddingBottom: 32 },
  header: { padding: 16, gap: 6 },
  actions: { flexDirection: 'row', gap: 8, marginTop: 8 },
  muted: { opacity: 0.6 },
});
