import { Link } from 'expo-router';
import { useState } from 'react';
import { ScrollView, StyleSheet } from 'react-native';
import { List, Searchbar } from 'react-native-paper';

import { useSearch } from '@/src/api/hooks';
import { ProductionRow } from '@/src/components/ProductionRow';

export default function SearchScreen() {
  const [q, setQ] = useState('');
  const { data } = useSearch(q);

  return (
    <ScrollView keyboardShouldPersistTaps="handled">
      <Searchbar
        style={styles.bar}
        placeholder="Постановка, актьор или театър"
        value={q}
        onChangeText={setQ}
        autoCorrect={false}
      />
      {data?.productions.map((p) => <ProductionRow key={`p${p.id}`} production={p} />)}
      {data && data.people.length > 0 && (
        <List.Section title="Хора">
          {data.people.map((person) => (
            <Link key={person.id} href={{ pathname: '/person/[id]', params: { id: person.id } }} asChild>
              <List.Item title={person.name} left={(props) => <List.Icon {...props} icon="account" />} />
            </Link>
          ))}
        </List.Section>
      )}
      {data && data.theatres.length > 0 && (
        <List.Section title="Театри">
          {data.theatres.map((t) => (
            <Link key={t.id} href={{ pathname: '/theatre/[id]', params: { id: t.id } }} asChild>
              <List.Item title={t.name} left={(props) => <List.Icon {...props} icon="domain" />} />
            </Link>
          ))}
        </List.Section>
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  bar: { margin: 16 },
});
