// utils/currency_formatter.dart
import 'package:intl/intl.dart';

String formatCurrency(double amount) {
  final format = NumberFormat.currency(
    symbol: '₦',
    decimalDigits: 2,
  );
  return format.format(amount);
}
