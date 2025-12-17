import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:google_maps_flutter/google_maps_flutter.dart';
import 'package:intl/intl.dart';
import '../../../../config/theme/app_theme.dart';
import '../../../../shared/widgets/modern_card.dart';
import '../../../../shared/widgets/primary_button.dart';
import '../../data/models/event_model.dart';
import '../../data/services/event_service.dart';

class EventDetailsScreen extends StatefulWidget {
  final int eventId;

  const EventDetailsScreen({super.key, required this.eventId});

  @override
  State<EventDetailsScreen> createState() => _EventDetailsScreenState();
}

class _EventDetailsScreenState extends State<EventDetailsScreen> {
  final EventService _eventService = EventService();
  bool _isLoading = true;
  bool _isCheckingIn = false;
  EventModel? _event;
  Position? _userPosition;
  double? _distanceFromEvent;
  bool _locationPermissionGranted = false;

  @override
  void initState() {
    super.initState();
    _loadEventDetails();
    _checkLocationPermission();
  }

  Future<void> _checkLocationPermission() async {
    try {
      LocationPermission permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }
      
      if (permission == LocationPermission.deniedForever) {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(
              content: Text('Location permission is permanently denied. Please enable it in settings.'),
            ),
          );
        }
        return;
      }

      if (permission == LocationPermission.whileInUse ||
          permission == LocationPermission.always) {
        setState(() => _locationPermissionGranted = true);
        _getCurrentLocation();
      }
    } catch (e) {
      debugPrint('Error checking location permission: $e');
    }
  }

  Future<void> _getCurrentLocation() async {
    try {
      final position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
      );
      setState(() => _userPosition = position);
      
      if (_event != null) {
        _calculateDistance();
      }
    } catch (e) {
      debugPrint('Error getting location: $e');
    }
  }

  void _calculateDistance() {
    if (_event == null || _userPosition == null) return;

    final distance = Geolocator.distanceBetween(
      _userPosition!.latitude,
      _userPosition!.longitude,
      _event!.locationLat,
      _event!.locationLng,
    );

    setState(() => _distanceFromEvent = distance);
  }

  Future<void> _loadEventDetails() async {
    try {
      setState(() => _isLoading = true);
      final event = await _eventService.getEventDetails(widget.eventId);
      if (mounted) {
        setState(() {
          _event = event;
          _isLoading = false;
        });
        
        if (_userPosition != null) {
          _calculateDistance();
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Error loading event: ${e.toString()}')),
        );
        setState(() => _isLoading = false);
      }
    }
  }

  Future<void> _handleCheckIn() async {
    if (_event == null) return;

    if (!_locationPermissionGranted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Location permission is required for check-in'),
        ),
      );
      return;
    }

    if (_userPosition == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Unable to get your location. Please try again.'),
        ),
      );
      await _getCurrentLocation();
      return;
    }

    setState(() => _isCheckingIn = true);

    try {
      final result = await _eventService.checkIn(
        eventId: _event!.id,
        latitude: _userPosition!.latitude,
        longitude: _userPosition!.longitude,
      );

      if (mounted) {
        if (result['success'] == true) {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(
              content: Text(result['message'] ?? 'Check-in successful!'),
              backgroundColor: AppTheme.success,
            ),
          );
          // Reload event details to update check-in status
          await _loadEventDetails();
        } else {
          final distance = result['distance'];
          final message = result['message'] ?? 'Check-in failed';
          
          showDialog(
            context: context,
            builder: (context) => AlertDialog(
              title: const Text('Check-in Failed'),
              content: Text(
                distance != null
                    ? '$message\n\nYou are ${distance.toStringAsFixed(2)}m away from the event location. Please move closer.'
                    : message,
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.pop(context),
                  child: const Text('OK'),
                ),
              ],
            ),
          );
        }
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Error checking in: ${e.toString()}'),
            backgroundColor: AppTheme.error,
          ),
        );
      }
    } finally {
      if (mounted) {
        setState(() => _isCheckingIn = false);
      }
    }
  }

  String _formatDateTime(DateTime dateTime) {
    return DateFormat('MMM dd, yyyy • hh:mm a').format(dateTime);
  }

  Color _getStatusColor(String status) {
    switch (status) {
      case 'active':
        return AppTheme.success;
      case 'upcoming':
        return AppTheme.info;
      case 'past':
        return AppTheme.textSecondaryLight;
      default:
        return AppTheme.textSecondaryLight;
    }
  }

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Event Details'),
        elevation: 0,
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator())
          : _event == null
              ? Center(
                  child: Text(
                    'Event not found',
                    style: AppTheme.bodyLarge,
                  ),
                )
              : SingleChildScrollView(
                  padding: const EdgeInsets.all(AppTheme.spacingMD),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // Event Title and Status
                      ModernCard(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Expanded(
                                  child: Text(
                                    _event!.title,
                                    style: AppTheme.headlineLarge.copyWith(
                                      fontWeight: FontWeight.w700,
                                    ),
                                  ),
                                ),
                                Container(
                                  padding: const EdgeInsets.symmetric(
                                    horizontal: AppTheme.spacingSM,
                                    vertical: 6,
                                  ),
                                  decoration: BoxDecoration(
                                    color: _getStatusColor(_event!.status)
                                        .withOpacity(0.1),
                                    borderRadius:
                                        BorderRadius.circular(AppTheme.radiusSM),
                                  ),
                                  child: Text(
                                    _event!.status.toUpperCase(),
                                    style: AppTheme.labelSmall.copyWith(
                                      color: _getStatusColor(_event!.status),
                                      fontWeight: FontWeight.w700,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                            if (_event!.description != null &&
                                _event!.description!.isNotEmpty) ...[
                              const SizedBox(height: AppTheme.spacingMD),
                              Text(
                                _event!.description!,
                                style: AppTheme.bodyLarge.copyWith(
                                  color: isDark
                                      ? AppTheme.textSecondaryDark
                                      : AppTheme.textSecondaryLight,
                                ),
                              ),
                            ],
                          ],
                        ),
                      ),
                      const SizedBox(height: AppTheme.spacingMD),

                      // Event Times
                      ModernCard(
                        child: Column(
                          children: [
                            _buildInfoRow(
                              Icons.access_time,
                              'Start Time',
                              _formatDateTime(_event!.startTime),
                              isDark,
                            ),
                            const Divider(),
                            _buildInfoRow(
                              Icons.event,
                              'End Time',
                              _formatDateTime(_event!.endTime),
                              isDark,
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: AppTheme.spacingMD),

                      // Map
                      ModernCard(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Icon(
                                  Icons.location_on,
                                  color: AppTheme.primaryColor,
                                ),
                                const SizedBox(width: AppTheme.spacingSM),
                                Text(
                                  'Event Location',
                                  style: AppTheme.headlineMedium.copyWith(
                                    fontWeight: FontWeight.w700,
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: AppTheme.spacingMD),
                            SizedBox(
                              height: 250,
                              child: GoogleMap(
                                initialCameraPosition: CameraPosition(
                                  target: LatLng(
                                    _event!.locationLat,
                                    _event!.locationLng,
                                  ),
                                  zoom: 15,
                                ),
                                onMapCreated: (controller) {
                                  // Map controller available if needed for future features
                                },
                                markers: {
                                  Marker(
                                    markerId: const MarkerId('event_location'),
                                    position: LatLng(
                                      _event!.locationLat,
                                      _event!.locationLng,
                                    ),
                                    infoWindow: InfoWindow(
                                      title: _event!.title,
                                    ),
                                  ),
                                  if (_userPosition != null)
                                    Marker(
                                      markerId: const MarkerId('user_location'),
                                      position: LatLng(
                                        _userPosition!.latitude,
                                        _userPosition!.longitude,
                                      ),
                                      icon: BitmapDescriptor.defaultMarkerWithHue(
                                        BitmapDescriptor.hueBlue,
                                      ),
                                      infoWindow: const InfoWindow(
                                        title: 'Your Location',
                                      ),
                                    ),
                                },
                                circles: {
                                  Circle(
                                    circleId: const CircleId('geofence'),
                                    center: LatLng(
                                      _event!.locationLat,
                                      _event!.locationLng,
                                    ),
                                    radius: _event!.geofenceRadius.toDouble(),
                                    fillColor: AppTheme.primaryColor
                                        .withOpacity(0.2),
                                    strokeColor: AppTheme.primaryColor,
                                    strokeWidth: 2,
                                  ),
                                },
                                myLocationEnabled: true,
                                myLocationButtonEnabled: true,
                              ),
                            ),
                            if (_distanceFromEvent != null) ...[
                              const SizedBox(height: AppTheme.spacingMD),
                              Container(
                                padding: const EdgeInsets.all(AppTheme.spacingSM),
                                decoration: BoxDecoration(
                                  color: _distanceFromEvent! <=
                                          _event!.geofenceRadius
                                      ? AppTheme.success.withOpacity(0.1)
                                      : AppTheme.warning.withOpacity(0.1),
                                  borderRadius:
                                      BorderRadius.circular(AppTheme.radiusSM),
                                ),
                                child: Row(
                                  children: [
                                    Icon(
                                      _distanceFromEvent! <=
                                              _event!.geofenceRadius
                                          ? Icons.check_circle
                                          : Icons.warning,
                                      color: _distanceFromEvent! <=
                                              _event!.geofenceRadius
                                          ? AppTheme.success
                                          : AppTheme.warning,
                                    ),
                                    const SizedBox(width: AppTheme.spacingSM),
                                    Expanded(
                                      child: Text(
                                        _distanceFromEvent! <=
                                                _event!.geofenceRadius
                                            ? 'You are within the event area (${_distanceFromEvent!.toStringAsFixed(2)}m away)'
                                            : 'You are ${_distanceFromEvent!.toStringAsFixed(2)}m away from the event location',
                                        style: AppTheme.bodyMedium.copyWith(
                                          color: _distanceFromEvent! <=
                                                  _event!.geofenceRadius
                                              ? AppTheme.success
                                              : AppTheme.warning,
                                          fontWeight: FontWeight.w600,
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ],
                        ),
                      ),
                      const SizedBox(height: AppTheme.spacingMD),

                      // Check-in Button
                      if (!_event!.hasCheckedIn &&
                          (_event!.isActive || _event!.isUpcoming))
                        PrimaryButton(
                          text: _isCheckingIn ? 'Checking In...' : 'Check In',
                          onPressed: _isCheckingIn ? null : _handleCheckIn,
                          icon: _isCheckingIn
                              ? null
                              : Icons.check_circle_outline,
                        ),
                      if (_event!.hasCheckedIn)
                        Container(
                          padding: const EdgeInsets.all(AppTheme.spacingMD),
                          decoration: BoxDecoration(
                            color: AppTheme.success.withOpacity(0.1),
                            borderRadius:
                                BorderRadius.circular(AppTheme.radiusMD),
                            border: Border.all(
                              color: AppTheme.success,
                              width: 2,
                            ),
                          ),
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.center,
                            children: [
                              Icon(
                                Icons.check_circle,
                                color: AppTheme.success,
                              ),
                              const SizedBox(width: AppTheme.spacingSM),
                              Text(
                                'You have checked in',
                                style: AppTheme.bodyLarge.copyWith(
                                  color: AppTheme.success,
                                  fontWeight: FontWeight.w700,
                                ),
                              ),
                            ],
                          ),
                        ),
                    ],
                  ),
                ),
    );
  }

  Widget _buildInfoRow(
    IconData icon,
    String label,
    String value,
    bool isDark,
  ) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: AppTheme.spacingSM),
      child: Row(
        children: [
          Icon(
            icon,
            size: 20,
            color: AppTheme.primaryColor,
          ),
          const SizedBox(width: AppTheme.spacingSM),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  label,
                  style: AppTheme.labelSmall.copyWith(
                    color: isDark
                        ? AppTheme.textSecondaryDark
                        : AppTheme.textSecondaryLight,
                  ),
                ),
                const SizedBox(height: 4),
                Text(
                  value,
                  style: AppTheme.bodyLarge.copyWith(
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

