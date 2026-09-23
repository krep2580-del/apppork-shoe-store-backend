import 'package:flutter_test/flutter_test.dart';
import 'package:customer_app/main.dart';

void main() {
  testWidgets('Customer App smoke test', (WidgetTester tester) async {
    await tester.pumpWidget(const ShoeStoreCustomerApp());
    expect(find.byType(ShoeStoreCustomerApp), findsOneWidget);
  });
}
