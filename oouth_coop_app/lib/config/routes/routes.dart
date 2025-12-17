import 'package:flutter/material.dart';
import '../../features/auth/presentation/screens/welcome_screen.dart';
import '../../features/auth/presentation/screens/forgot_password_screen.dart';
import '../../features/auth/presentation/screens/login_screen.dart';
import '../../features/auth/presentation/screens/signup_screen.dart';
import '../../features/home/presentation/screens/home_screen.dart';
import '../../features/transactions/presentation/screens/transaction_details_screen.dart';
import '../../features/payments/presentation/screens/online_payment_screen.dart';
import '../../features/settings/presentation/screens/settings_screen.dart';
import '../../features/complaints/presentation/screens/complaints_screen.dart';
import '../../features/notifications/presentation/screens/notifications_screen.dart';
import '../../features/products/presentation/screens/product_screen.dart';
import '../../features/support/presentation/screens/support_screen.dart';
import '../../features/account/presentation/screens/account_screen.dart';
import '../../features/loans/presentation/screens/loan_tracker_screen.dart';
import '../../features/loans/presentation/screens/loan_request_form_screen.dart';
import '../../features/loans/presentation/screens/loan_request_status_screen.dart';
import '../../features/loans/presentation/screens/guarantor_requests_screen.dart';
import '../../features/profile/presentation/screens/profile_enhancement_screen.dart';
import '../../features/profile/presentation/screens/personal_info_screen.dart';
import '../../features/profile/presentation/screens/change_password_screen.dart';
import '../../features/profile/presentation/screens/emergency_contact_screen.dart';
import '../../../../features/profile/presentation/screens/bank_account_screen.dart';
import '../../../../features/events/presentation/screens/events_list_screen.dart';

class AppRoutes {
  static const String welcome = '/';
  static const String login = '/login';
  static const String home = '/home';
  static const String transactions = '/transactions';
  static const String payments = '/payments';
  static const String settings = '/settings';
  static const String complaints = '/complaints';
  static const String notifications = '/notifications';
  static const String products = '/products';
  static const String support = '/support';
  static const String account = '/account';
  static const String profileEnhancement = '/profile-enhancement';
  static const String personalInfo = '/personal-info';
  static const String changePassword = '/change-password';
  static const String emergencyContact = '/emergency-contact';
  static const String loanTracker = '/loan-tracker';
  static const String loanRequestForm = '/loan-request-form';
  static const String loanRequestStatus = '/loan-request-status';
  static const String guarantorRequests = '/guarantor-requests';
  static const String forgotPassword = '/forgot-password';
  static const String signup = '/signup';
  static const String bankAccount = '/bank-account';
  static const String events = '/events';
  static const String eventDetails = '/event-details';

  // Routes map
  static Map<String, WidgetBuilder> get routes => {
        welcome: (context) => const WelcomeScreen(),
        login: (context) => const LoginScreen(),
        home: (context) => const HomeScreen(),
        transactions: (context) => const TransactionDetailsScreen(),
        payments: (context) => const OnlinePaymentScreen(),
        settings: (context) => const SettingsScreen(),
        complaints: (context) => const ComplaintsScreen(),
        notifications: (context) => const NotificationsScreen(),
        products: (context) => const ProductScreen(),
        support: (context) => const SupportScreen(),
        account: (context) => const AccountScreen(),
        loanTracker: (context) => const LoanTrackerScreen(),
        loanRequestForm: (context) => const LoanRequestFormScreen(),
        loanRequestStatus: (context) => const LoanRequestStatusScreen(),
        guarantorRequests: (context) => const GuarantorRequestsScreen(),
        profileEnhancement: (context) => const ProfileEnhancementScreen(),
        personalInfo: (context) => const PersonalInfoScreen(),
        changePassword: (context) => const ChangePasswordScreen(),
        forgotPassword: (context) => const ForgotPasswordScreen(),
        signup: (context) => const SignupScreen(),
        bankAccount: (context) => const BankAccountScreen(),
        events: (context) => const EventsListScreen(),
        // Do not include emergencyContact and eventDetails here since they require parameters
      };

  // Navigation methods
  static void navigateToHome(BuildContext context) {
    Navigator.pushNamedAndRemoveUntil(
      context,
      home,
      (route) => false,
    );
  }

  static Future<void> navigateToForgotPassword(BuildContext context) async {
    await Navigator.pushNamed(context, forgotPassword);
  }

  static void navigateToLogin(BuildContext context) {
    Navigator.pushNamedAndRemoveUntil(
      context,
      login,
      (route) => false,
    );
  }

  static Future<void> navigateToProfileEnhancement(BuildContext context) async {
    await Navigator.pushNamed(context, profileEnhancement);
  }

  static Future<void> navigateTosignUp(BuildContext context) async {
    await Navigator.pushNamed(context, signup);
  }

  static Future<void> navigateToPersonalInfo(BuildContext context) async {
    final result = await Navigator.pushNamed(context, personalInfo);
    if (result == true && context.mounted) {
      Navigator.pushReplacementNamed(
          context, account); // Refresh account screen
    }
  }

  static Future<void> navigateToChangePassword(BuildContext context) async {
    await Navigator.pushNamed(context, changePassword);
  }

  static Future<void> navigateToEmergencyContact(
      BuildContext context, VoidCallback onUpdate) async {
    await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => EmergencyContactScreen(onUpdate: onUpdate),
      ),
    );
  }

  static void navigateToWelcome(BuildContext context) {
    Navigator.pushNamedAndRemoveUntil(
      context,
      welcome,
      (route) => false,
    );
  }

  static Future<void> navigateToTransactions(BuildContext context) async {
    await Navigator.pushNamed(context, transactions);
  }

  static Future<void> navigateToPayments(BuildContext context) async {
    await Navigator.pushNamed(context, payments);
  }

  static Future<void> navigateToSettings(BuildContext context) async {
    await Navigator.pushNamed(context, settings);
  }

  static Future<void> navigateToComplaints(BuildContext context) async {
    await Navigator.pushNamed(context, complaints);
  }

  static void navigateToProducts(BuildContext context) {
    Navigator.pushReplacementNamed(context, products);
  }

  static Future<void> navigateToSupport(BuildContext context) async {
    await Navigator.pushNamed(context, support);
  }

  static Future<void> navigateToBankAccount(BuildContext context) async {
    await Navigator.push(
      context,
      MaterialPageRoute(
        builder: (context) => const BankAccountScreen(),
      ),
    );
  }

  static Future<void> navigateToGuarantorRequests(BuildContext context) async {
    await Navigator.pushNamed(context, guarantorRequests);
  }

  static void navigateToAccount(BuildContext context) {
    Navigator.pushReplacementNamed(context, account);
  }

  // Pop until route method
  static void popUntilHome(BuildContext context) {
    Navigator.popUntil(context, ModalRoute.withName(home));
  }

  // Go back method
  static void goBack(BuildContext context) {
    Navigator.pop(context);
  }

  // Replace current route method
  static void replaceWith(BuildContext context, String routeName) {
    Navigator.pushReplacementNamed(context, routeName);
  }
}
