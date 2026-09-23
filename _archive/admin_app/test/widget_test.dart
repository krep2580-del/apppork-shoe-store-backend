import 'package:flutter_test/flutter_test.dart';
import 'package:admin_app/main.dart';

void main() {
  testWidgets('Admin App smoke test', (WidgetTester tester) async {
    await tester.pumpWidget(const ShoeStoreAdminApp());
    expect(find.byType(ShoeStoreAdminApp), findsOneWidget);
  });
}
