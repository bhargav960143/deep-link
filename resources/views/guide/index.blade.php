<x-layouts.app title="Integration Guide" :tenant="$tenant">

<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-900">Mobile Integration Guide</h1>
    <p class="mt-1 text-sm text-gray-500">How to handle deep links in your mobile app — pick your framework below.</p>
</div>

{{-- How it works banner --}}
<div class="bg-indigo-50 border border-indigo-100 rounded-xl p-5 mb-8">
    <h2 class="text-sm font-semibold text-indigo-900 mb-2">How DeepLink works</h2>
    <ol class="text-sm text-indigo-800 space-y-1 list-decimal list-inside">
        <li>User taps a short link: <code class="bg-indigo-100 px-1.5 py-0.5 rounded text-xs font-mono">https://{{ $primaryDomain }}/l/abc123</code></li>
        <li>Server checks platform, redirects to your app via Universal Link / App Link</li>
        <li>If app not installed, falls back to App Store / Play Store (or custom URL)</li>
        <li>Your app receives the original destination path and navigates accordingly</li>
    </ol>
    <p class="text-xs text-indigo-600 mt-3">
        This server automatically serves
        <code class="bg-indigo-100 px-1 rounded font-mono">/.well-known/apple-app-site-association</code> and
        <code class="bg-indigo-100 px-1 rounded font-mono">/.well-known/assetlinks.json</code>
        for your registered apps — you don't need to host these files yourself.
    </p>
</div>

{{-- Your registered apps --}}
@if($apps->isNotEmpty())
<div class="bg-white rounded-xl border border-gray-200 p-5 mb-8">
    <h2 class="text-sm font-semibold text-gray-900 mb-3">Your registered apps</h2>
    <p class="text-xs text-gray-500 mb-4">Use these values in the code snippets below.</p>
    <div class="space-y-3">
        @foreach($apps as $app)
        <div class="bg-gray-50 rounded-lg p-4 text-xs font-mono space-y-1">
            <div class="font-sans font-medium text-gray-800 text-sm mb-2">{{ $app->name }}
                <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 capitalize font-sans">{{ $app->platform }}</span>
            </div>
            @if($app->ios_bundle_id)
            <div><span class="text-gray-400">iOS Bundle ID:</span> <span class="text-indigo-700">{{ $app->ios_bundle_id }}</span></div>
            @endif
            @if($app->ios_team_id)
            <div><span class="text-gray-400">Apple Team ID:</span> <span class="text-indigo-700">{{ $app->ios_team_id }}</span></div>
            @endif
            @if($app->android_package_name)
            <div><span class="text-gray-400">Android Package:</span> <span class="text-green-700">{{ $app->android_package_name }}</span></div>
            @endif
            @if($app->uri_scheme)
            <div><span class="text-gray-400">URI Scheme:</span> <span class="text-purple-700">{{ $app->uri_scheme }}://</span></div>
            @endif
            @foreach($domains as $domain)
            <div><span class="text-gray-400">Deep Link Domain:</span> <span class="text-gray-800">{{ $domain->domain }}</span></div>
            @endforeach
        </div>
        @endforeach
    </div>
</div>
@else
<div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-8 text-sm text-amber-800">
    No apps registered yet. <a href="{{ route('apps.create') }}" class="underline font-medium">Register an app</a> to see your specific Bundle ID / Package Name in the snippets below.
</div>
@endif

{{-- Framework tabs --}}
<div x-data="{ tab: 'expo' }">

    {{-- Tab bar --}}
    <div class="flex gap-1 flex-wrap mb-6 bg-gray-100 p-1 rounded-xl">
        @php
            $tabs = [
                'expo'     => 'React Native (Expo)',
                'rn'       => 'React Native (Bare)',
                'flutter'  => 'Flutter',
                'kotlin'   => 'Kotlin (Android)',
                'swift'    => 'Swift (iOS)',
                'xamarin'  => 'Xamarin / MAUI',
            ];
        @endphp
        @foreach($tabs as $key => $label)
        <button @click="tab = '{{ $key }}'"
                :class="tab === '{{ $key }}' ? 'bg-white text-indigo-700 shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                class="px-3 py-1.5 rounded-lg text-sm font-medium transition-colors">
            {{ $label }}
        </button>
        @endforeach
    </div>

    {{-- ─── EXPO ─── --}}
    <div x-show="tab === 'expo'" x-cloak>
        <div class="space-y-6">
            <x-guide.section title="Step 1 — Install expo-linking">
                <x-guide.code lang="bash">npx expo install expo-linking</x-guide.code>
            </x-guide.section>

            <x-guide.section title="Step 2 — Configure app.json / app.config.js">
                <p class="text-sm text-gray-600 mb-3">Add your domain under <code class="bg-gray-100 px-1 rounded text-xs font-mono">intentFilters</code> (Android App Links) and <code class="bg-gray-100 px-1 rounded text-xs font-mono">associatedDomains</code> (iOS Universal Links).</p>
                <x-guide.code lang="json">{
  "expo": {
    "scheme": "{{ $apps->first()?->uri_scheme ?? 'myapp' }}",
    "ios": {
      "bundleIdentifier": "{{ $apps->first()?->ios_bundle_id ?? 'com.yourcompany.app' }}",
      "associatedDomains": [
@foreach($domains as $domain)
        "applinks:{{ $domain->domain }}"{{ !$loop->last ? ',' : '' }}
@endforeach
@if($domains->isEmpty())
        "applinks:{{ $primaryDomain }}"
@endif
      ]
    },
    "android": {
      "package": "{{ $apps->first()?->android_package_name ?? 'com.yourcompany.app' }}",
      "intentFilters": [
        {
          "action": "VIEW",
          "autoVerify": true,
          "data": [
@foreach($domains as $domain)
            {
              "scheme": "https",
              "host": "{{ $domain->domain }}",
              "pathPrefix": "/l/"
            }{{ !$loop->last ? ',' : '' }}
@endforeach
@if($domains->isEmpty())
            {
              "scheme": "https",
              "host": "{{ $primaryDomain }}",
              "pathPrefix": "/l/"
            }
@endif
          ],
          "category": ["BROWSABLE", "DEFAULT"]
        }
      ]
    }
  }
}</x-guide.code>
            </x-guide.section>

            <x-guide.section title="Step 3 — Handle incoming links in your app">
                <x-guide.code lang="jsx">import { useEffect } from 'react';
import * as Linking from 'expo-linking';
import { useNavigation } from '@react-navigation/native';

export default function App() {
  const navigation = useNavigation();

  useEffect(() => {
    // Handle link when app is already open
    const subscription = Linking.addEventListener('url', ({ url }) => {
      handleDeepLink(url);
    });

    // Handle link that launched the app from closed state
    Linking.getInitialURL().then((url) => {
      if (url) handleDeepLink(url);
    });

    return () => subscription.remove();
  }, []);

  function handleDeepLink(url) {
    const parsed = Linking.parse(url);
    // parsed.path will be the destination_path you set in the link
    // e.g. "/products/123" or "/profile/456"
    if (parsed.path) {
      navigation.navigate(parsed.path); // adapt to your router
    }
  }

  return (/* your app */);
}</x-guide.code>
            </x-guide.section>

            <x-guide.section title="Step 4 — Rebuild (required after config changes)">
                <x-guide.code lang="bash"># iOS
npx expo run:ios

# Android
npx expo run:android</x-guide.code>
                <p class="text-xs text-gray-500 mt-2">Expo Go does not support Universal Links or App Links. You must use a development build or production build.</p>
            </x-guide.section>
        </div>
    </div>

    {{-- ─── REACT NATIVE BARE ─── --}}
    <div x-show="tab === 'rn'" x-cloak>
        <div class="space-y-6">

            <x-guide.section title="Android — AndroidManifest.xml">
                <p class="text-sm text-gray-600 mb-3">Add an <code class="bg-gray-100 px-1 rounded text-xs font-mono">intent-filter</code> with <code class="bg-gray-100 px-1 rounded text-xs font-mono">autoVerify="true"</code> inside your main <code class="bg-gray-100 px-1 rounded text-xs font-mono">&lt;activity&gt;</code>.</p>
                <x-guide.code lang="xml">&lt;!-- android/app/src/main/AndroidManifest.xml --&gt;
&lt;activity android:name=".MainActivity" ...&gt;

  &lt;!-- existing intent-filter for launcher --&gt;

  &lt;!-- App Links (verified deep links) --&gt;
  &lt;intent-filter android:autoVerify="true"&gt;
    &lt;action android:name="android.intent.action.VIEW" /&gt;
    &lt;category android:name="android.intent.category.DEFAULT" /&gt;
    &lt;category android:name="android.intent.category.BROWSABLE" /&gt;
@foreach($domains as $domain)
    &lt;data android:scheme="https" android:host="{{ $domain->domain }}" android:pathPrefix="/l/" /&gt;
@endforeach
@if($domains->isEmpty())
    &lt;data android:scheme="https" android:host="{{ $primaryDomain }}" android:pathPrefix="/l/" /&gt;
@endif
  &lt;/intent-filter&gt;

  &lt;!-- URI scheme fallback --&gt;
  &lt;intent-filter&gt;
    &lt;action android:name="android.intent.action.VIEW" /&gt;
    &lt;category android:name="android.intent.category.DEFAULT" /&gt;
    &lt;category android:name="android.intent.category.BROWSABLE" /&gt;
    &lt;data android:scheme="{{ $apps->first()?->uri_scheme ?? 'myapp' }}" /&gt;
  &lt;/intent-filter&gt;

&lt;/activity&gt;</x-guide.code>
            </x-guide.section>

            <x-guide.section title="iOS — Entitlements (Associated Domains)">
                <p class="text-sm text-gray-600 mb-3">In Xcode, go to <strong>Signing &amp; Capabilities → + Capability → Associated Domains</strong> and add:</p>
                <x-guide.code lang="text">@foreach($domains as $domain)
applinks:{{ $domain->domain }}
@endforeach
@if($domains->isEmpty())
applinks:{{ $primaryDomain }}
@endif</x-guide.code>
                <p class="text-sm text-gray-600 mt-3 mb-2">Or edit <code class="bg-gray-100 px-1 rounded text-xs font-mono">ios/YourApp/YourApp.entitlements</code> directly:</p>
                <x-guide.code lang="xml">&lt;?xml version="1.0" encoding="UTF-8"?&gt;
&lt;!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" ...&gt;
&lt;plist version="1.0"&gt;
&lt;dict&gt;
  &lt;key&gt;com.apple.developer.associated-domains&lt;/key&gt;
  &lt;array&gt;
@foreach($domains as $domain)
    &lt;string&gt;applinks:{{ $domain->domain }}&lt;/string&gt;
@endforeach
@if($domains->isEmpty())
    &lt;string&gt;applinks:{{ $primaryDomain }}&lt;/string&gt;
@endif
  &lt;/array&gt;
&lt;/dict&gt;
&lt;/plist&gt;</x-guide.code>
            </x-guide.section>

            <x-guide.section title="Handle links in React Native (JS)">
                <x-guide.code lang="jsx">import { useEffect } from 'react';
import { Linking } from 'react-native';

function useDeepLink(onLink) {
  useEffect(() => {
    const sub = Linking.addEventListener('url', ({ url }) => onLink(url));
    Linking.getInitialURL().then((url) => { if (url) onLink(url); });
    return () => sub.remove();
  }, []);
}

// Usage
useDeepLink((url) => {
  const path = new URL(url).pathname; // e.g. "/products/123"
  // navigate to path
});</x-guide.code>
            </x-guide.section>
        </div>
    </div>

    {{-- ─── FLUTTER ─── --}}
    <div x-show="tab === 'flutter'" x-cloak>
        <div class="space-y-6">

            <x-guide.section title="Step 1 — Add go_router (recommended) or uni_links">
                <x-guide.code lang="yaml"># pubspec.yaml
dependencies:
  go_router: ^14.0.0   # handles deep links automatically
  # OR if you prefer manual handling:
  # app_links: ^6.0.0</x-guide.code>
            </x-guide.section>

            <x-guide.section title="Step 2 — Android: AndroidManifest.xml">
                <x-guide.code lang="xml">&lt;!-- android/app/src/main/AndroidManifest.xml --&gt;
&lt;activity android:name=".MainActivity"
          android:launchMode="singleTask"
          android:exported="true"&gt;

  &lt;intent-filter android:autoVerify="true"&gt;
    &lt;action android:name="android.intent.action.VIEW" /&gt;
    &lt;category android:name="android.intent.category.DEFAULT" /&gt;
    &lt;category android:name="android.intent.category.BROWSABLE" /&gt;
@foreach($domains as $domain)
    &lt;data android:scheme="https" android:host="{{ $domain->domain }}" android:pathPrefix="/l/" /&gt;
@endforeach
@if($domains->isEmpty())
    &lt;data android:scheme="https" android:host="{{ $primaryDomain }}" android:pathPrefix="/l/" /&gt;
@endif
  &lt;/intent-filter&gt;

  &lt;!-- URI scheme fallback --&gt;
  &lt;intent-filter&gt;
    &lt;action android:name="android.intent.action.VIEW" /&gt;
    &lt;category android:name="android.intent.category.DEFAULT" /&gt;
    &lt;category android:name="android.intent.category.BROWSABLE" /&gt;
    &lt;data android:scheme="{{ $apps->first()?->uri_scheme ?? 'myapp' }}" /&gt;
  &lt;/intent-filter&gt;
&lt;/activity&gt;</x-guide.code>
            </x-guide.section>

            <x-guide.section title="Step 3 — iOS: Info.plist (URI scheme fallback)">
                <x-guide.code lang="xml">&lt;!-- ios/Runner/Info.plist --&gt;
&lt;key&gt;CFBundleURLTypes&lt;/key&gt;
&lt;array&gt;
  &lt;dict&gt;
    &lt;key&gt;CFBundleTypeRole&lt;/key&gt;
    &lt;string&gt;Editor&lt;/string&gt;
    &lt;key&gt;CFBundleURLSchemes&lt;/key&gt;
    &lt;array&gt;
      &lt;string&gt;{{ $apps->first()?->uri_scheme ?? 'myapp' }}&lt;/string&gt;
    &lt;/array&gt;
  &lt;/dict&gt;
&lt;/array&gt;</x-guide.code>
                <p class="text-sm text-gray-600 mt-3">For Universal Links on iOS, add Associated Domains in Xcode: <strong>Signing &amp; Capabilities → Associated Domains</strong> → add <code class="bg-gray-100 px-1 rounded text-xs font-mono">applinks:{{ $domains->first()?->domain ?? $primaryDomain }}</code>.</p>
            </x-guide.section>

            <x-guide.section title="Step 4 — Handle links with go_router">
                <x-guide.code lang="dart">import 'package:go_router/go_router.dart';

final router = GoRouter(
  initialLocation: '/',
  // go_router automatically intercepts incoming deep links
  routes: [
    GoRoute(path: '/', builder: (ctx, state) => const HomeScreen()),
    GoRoute(
      // Match the destination_path you set in your DeepLink dashboard
      path: '/products/:id',
      builder: (ctx, state) => ProductScreen(id: state.pathParameters['id']!),
    ),
    GoRoute(
      path: '/profile/:id',
      builder: (ctx, state) => ProfileScreen(id: state.pathParameters['id']!),
    ),
  ],
);

// In main.dart
MaterialApp.router(routerConfig: router);</x-guide.code>
            </x-guide.section>

            <x-guide.section title="Alternative — Manual handling with app_links">
                <x-guide.code lang="dart">import 'package:app_links/app_links.dart';

final appLinks = AppLinks();

// In initState or main
appLinks.uriLinkStream.listen((uri) {
  // uri.path is the destination_path from your link
  // e.g. "/products/123"
  router.go(uri.path);
});</x-guide.code>
            </x-guide.section>
        </div>
    </div>

    {{-- ─── KOTLIN (ANDROID NATIVE) ─── --}}
    <div x-show="tab === 'kotlin'" x-cloak>
        <div class="space-y-6">

            <x-guide.section title="Step 1 — AndroidManifest.xml">
                <x-guide.code lang="xml">&lt;!-- AndroidManifest.xml --&gt;
&lt;activity
    android:name=".MainActivity"
    android:launchMode="singleTask"
    android:exported="true"&gt;

  &lt;intent-filter&gt;
    &lt;action android:name="android.intent.action.MAIN" /&gt;
    &lt;category android:name="android.intent.category.LAUNCHER" /&gt;
  &lt;/intent-filter&gt;

  &lt;!-- App Links (verified HTTPS deep links) --&gt;
  &lt;intent-filter android:autoVerify="true"&gt;
    &lt;action android:name="android.intent.action.VIEW" /&gt;
    &lt;category android:name="android.intent.category.DEFAULT" /&gt;
    &lt;category android:name="android.intent.category.BROWSABLE" /&gt;
@foreach($domains as $domain)
    &lt;data
        android:scheme="https"
        android:host="{{ $domain->domain }}"
        android:pathPrefix="/l/" /&gt;
@endforeach
@if($domains->isEmpty())
    &lt;data
        android:scheme="https"
        android:host="{{ $primaryDomain }}"
        android:pathPrefix="/l/" /&gt;
@endif
  &lt;/intent-filter&gt;

  &lt;!-- URI scheme fallback --&gt;
  &lt;intent-filter&gt;
    &lt;action android:name="android.intent.action.VIEW" /&gt;
    &lt;category android:name="android.intent.category.DEFAULT" /&gt;
    &lt;category android:name="android.intent.category.BROWSABLE" /&gt;
    &lt;data android:scheme="{{ $apps->first()?->uri_scheme ?? 'myapp' }}" /&gt;
  &lt;/intent-filter&gt;

&lt;/activity&gt;</x-guide.code>
            </x-guide.section>

            <x-guide.section title="Step 2 — Handle the intent in MainActivity.kt">
                <x-guide.code lang="kotlin">// MainActivity.kt
class MainActivity : AppCompatActivity() {

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContentView(R.layout.activity_main)
        handleDeepLinkIntent(intent)
    }

    // Called when app is already open and link is tapped
    override fun onNewIntent(intent: Intent) {
        super.onNewIntent(intent)
        handleDeepLinkIntent(intent)
    }

    private fun handleDeepLinkIntent(intent: Intent) {
        if (intent.action != Intent.ACTION_VIEW) return

        val uri = intent.data ?: return
        val path = uri.path ?: return  // e.g. "/products/123"

        // Navigate based on path
        when {
            path.startsWith("/products/") -> {
                val id = path.removePrefix("/products/")
                navigateToProduct(id)
            }
            path.startsWith("/profile/") -> {
                val id = path.removePrefix("/profile/")
                navigateToProfile(id)
            }
            else -> {
                // fallback — open home or show not found
            }
        }
    }
}</x-guide.code>
            </x-guide.section>

            <x-guide.section title="Step 3 — Verify App Links (Android 6+)">
                <p class="text-sm text-gray-600 mb-3">After installing the app, force-verify App Links:</p>
                <x-guide.code lang="bash">adb shell pm set-app-links --package {{ $apps->first()?->android_package_name ?? 'com.yourcompany.app' }} 2 all</x-guide.code>
                <p class="text-sm text-gray-600 mt-3">Check verification status:</p>
                <x-guide.code lang="bash">adb shell pm get-app-links {{ $apps->first()?->android_package_name ?? 'com.yourcompany.app' }}</x-guide.code>
                <p class="text-xs text-gray-500 mt-2">Expected output: <code class="bg-gray-100 px-1 rounded font-mono">STATUS: verified</code>. Verification requires a live device with internet access.</p>
            </x-guide.section>
        </div>
    </div>

    {{-- ─── SWIFT (IOS NATIVE) ─── --}}
    <div x-show="tab === 'swift'" x-cloak>
        <div class="space-y-6">

            <x-guide.section title="Step 1 — Add Associated Domains capability">
                <p class="text-sm text-gray-600 mb-3">In Xcode: select your target → <strong>Signing &amp; Capabilities</strong> → <strong>+ Capability</strong> → <strong>Associated Domains</strong>. Add each domain:</p>
                <x-guide.code lang="text">@foreach($domains as $domain)
applinks:{{ $domain->domain }}
@endforeach
@if($domains->isEmpty())
applinks:{{ $primaryDomain }}
@endif</x-guide.code>
                <p class="text-xs text-gray-500 mt-2">This adds the entitlement to your app. The server already hosts the AASA file at <code class="bg-gray-100 px-1 rounded font-mono">/.well-known/apple-app-site-association</code>.</p>
            </x-guide.section>

            <x-guide.section title="Step 2 — Add URI scheme (fallback)">
                <p class="text-sm text-gray-600 mb-3">In Xcode: select your target → <strong>Info</strong> → <strong>URL Types</strong> → click <strong>+</strong> and add:</p>
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 text-sm font-mono space-y-1">
                    <div><span class="text-gray-500">Identifier:</span> {{ $apps->first()?->ios_bundle_id ?? 'com.yourcompany.app' }}</div>
                    <div><span class="text-gray-500">URL Schemes:</span> {{ $apps->first()?->uri_scheme ?? 'myapp' }}</div>
                    <div><span class="text-gray-500">Role:</span> Editor</div>
                </div>
            </x-guide.section>

            <x-guide.section title="Step 3 — Handle Universal Links in SceneDelegate.swift">
                <x-guide.code lang="swift">// SceneDelegate.swift

func scene(_ scene: UIScene,
           continue userActivity: NSUserActivity) {
    guard userActivity.activityType == NSUserActivityTypeBrowsingWeb,
          let url = userActivity.webpageURL else { return }
    handleDeepLink(url: url)
}

// Called when app is already in foreground
func scene(_ scene: UIScene,
           openURLContexts URLContexts: Set&lt;UIOpenURLContext&gt;) {
    guard let url = URLContexts.first?.url else { return }
    handleDeepLink(url: url)
}

private func handleDeepLink(url: URL) {
    let path = url.path  // e.g. "/products/123"

    guard let windowScene = UIApplication.shared.connectedScenes.first as? UIWindowScene,
          let nav = windowScene.windows.first?.rootViewController as? UINavigationController
    else { return }

    switch true {
    case path.hasPrefix("/products/"):
        let id = String(path.dropFirst("/products/".count))
        let vc = ProductViewController(productId: id)
        nav.pushViewController(vc, animated: true)

    case path.hasPrefix("/profile/"):
        let id = String(path.dropFirst("/profile/".count))
        let vc = ProfileViewController(userId: id)
        nav.pushViewController(vc, animated: true)

    default:
        break
    }
}</x-guide.code>
            </x-guide.section>

            <x-guide.section title="Step 3 (alternative) — SwiftUI with .onOpenURL">
                <x-guide.code lang="swift">// YourApp.swift (SwiftUI)
@main
struct YourApp: App {
    var body: some Scene {
        WindowGroup {
            ContentView()
                .onOpenURL { url in
                    handleDeepLink(url: url)
                }
        }
    }

    func handleDeepLink(url: URL) {
        let path = url.path  // e.g. "/products/123"
        // Post a Notification, update @State, or use NavigationPath
        NotificationCenter.default.post(
            name: .deepLinkReceived,
            object: nil,
            userInfo: ["path": path]
        )
    }
}

extension Notification.Name {
    static let deepLinkReceived = Notification.Name("deepLinkReceived")
}</x-guide.code>
            </x-guide.section>

            <x-guide.section title="Step 4 — Test on device">
                <p class="text-sm text-gray-600 mb-3">Test Universal Links from Safari or another app using:</p>
                <x-guide.code lang="bash">xcrun simctl openurl booted "https://{{ $domains->first()?->domain ?? $primaryDomain }}/l/YOUR_SHORT_CODE"</x-guide.code>
                <p class="text-xs text-gray-500 mt-2">Universal Links don't work in Simulator with Safari directly — use the above command or a real device.</p>
            </x-guide.section>
        </div>
    </div>

    {{-- ─── XAMARIN / MAUI ─── --}}
    <div x-show="tab === 'xamarin'" x-cloak>
        <div class="space-y-6">

            <x-guide.section title="Android — AndroidManifest.xml">
                <p class="text-sm text-gray-600 mb-3">In <code class="bg-gray-100 px-1 rounded text-xs font-mono">Platforms/Android/AndroidManifest.xml</code> (MAUI) or <code class="bg-gray-100 px-1 rounded text-xs font-mono">Properties/AndroidManifest.xml</code> (Xamarin.Forms):</p>
                <x-guide.code lang="xml">&lt;activity android:name="com.microsoft.intune.mam.client.app.MAMActivity"
          android:launchMode="singleTop"&gt;

  &lt;intent-filter android:autoVerify="true"&gt;
    &lt;action android:name="android.intent.action.VIEW" /&gt;
    &lt;category android:name="android.intent.category.DEFAULT" /&gt;
    &lt;category android:name="android.intent.category.BROWSABLE" /&gt;
@foreach($domains as $domain)
    &lt;data android:scheme="https"
          android:host="{{ $domain->domain }}"
          android:pathPrefix="/l/" /&gt;
@endforeach
@if($domains->isEmpty())
    &lt;data android:scheme="https"
          android:host="{{ $primaryDomain }}"
          android:pathPrefix="/l/" /&gt;
@endif
  &lt;/intent-filter&gt;

  &lt;intent-filter&gt;
    &lt;action android:name="android.intent.action.VIEW" /&gt;
    &lt;category android:name="android.intent.category.DEFAULT" /&gt;
    &lt;category android:name="android.intent.category.BROWSABLE" /&gt;
    &lt;data android:scheme="{{ $apps->first()?->uri_scheme ?? 'myapp' }}" /&gt;
  &lt;/intent-filter&gt;
&lt;/activity&gt;</x-guide.code>
            </x-guide.section>

            <x-guide.section title="Android — Handle Intent in MainActivity.cs">
                <x-guide.code lang="csharp">// Platforms/Android/MainActivity.cs (MAUI)
// or MainActivity.cs (Xamarin.Android)

protected override void OnCreate(Bundle savedInstanceState)
{
    base.OnCreate(savedInstanceState);
    HandleDeepLink(Intent);
}

protected override void OnNewIntent(Intent intent)
{
    base.OnNewIntent(intent);
    HandleDeepLink(intent);
}

private void HandleDeepLink(Intent intent)
{
    if (intent?.Action != Intent.ActionView) return;

    var uri = intent.Data;
    if (uri == null) return;

    var path = uri.Path; // e.g. "/products/123"

    // Send to shared code via MessagingCenter or a service
    MessagingCenter.Send&lt;object, string&gt;(this, "DeepLink", path);
}</x-guide.code>
            </x-guide.section>

            <x-guide.section title="iOS — Info.plist (URI scheme)">
                <x-guide.code lang="xml">&lt;!-- Platforms/iOS/Info.plist (MAUI) or Info.plist (Xamarin.iOS) --&gt;
&lt;key&gt;CFBundleURLTypes&lt;/key&gt;
&lt;array&gt;
  &lt;dict&gt;
    &lt;key&gt;CFBundleURLSchemes&lt;/key&gt;
    &lt;array&gt;
      &lt;string&gt;{{ $apps->first()?->uri_scheme ?? 'myapp' }}&lt;/string&gt;
    &lt;/array&gt;
  &lt;/dict&gt;
&lt;/array&gt;</x-guide.code>
            </x-guide.section>

            <x-guide.section title="iOS — Associated Domains Entitlement">
                <p class="text-sm text-gray-600 mb-3">Add <code class="bg-gray-100 px-1 rounded text-xs font-mono">Entitlements.plist</code> (or update existing):</p>
                <x-guide.code lang="xml">&lt;?xml version="1.0" encoding="UTF-8"?&gt;
&lt;!DOCTYPE plist PUBLIC "-//Apple//DTD PLIST 1.0//EN" ...&gt;
&lt;plist version="1.0"&gt;
&lt;dict&gt;
  &lt;key&gt;com.apple.developer.associated-domains&lt;/key&gt;
  &lt;array&gt;
@foreach($domains as $domain)
    &lt;string&gt;applinks:{{ $domain->domain }}&lt;/string&gt;
@endforeach
@if($domains->isEmpty())
    &lt;string&gt;applinks:{{ $primaryDomain }}&lt;/string&gt;
@endif
  &lt;/array&gt;
&lt;/dict&gt;
&lt;/plist&gt;</x-guide.code>
            </x-guide.section>

            <x-guide.section title="iOS — Handle Universal Links in AppDelegate.cs">
                <x-guide.code lang="csharp">// Platforms/iOS/AppDelegate.cs (MAUI)
// or AppDelegate.cs (Xamarin.iOS)

public override bool ContinueUserActivity(
    UIApplication application,
    NSUserActivity userActivity,
    UIApplicationRestorationHandler completionHandler)
{
    if (userActivity.ActivityType == NSUserActivityType.BrowsingWeb)
    {
        var url = userActivity.WebPageUrl;
        var path = url?.Path; // e.g. "/products/123"

        if (path != null)
            MessagingCenter.Send&lt;object, string&gt;(this, "DeepLink", path);
    }
    return true;
}

// URI scheme fallback
public override bool OpenUrl(
    UIApplication app, NSUrl url,
    NSDictionary options)
{
    var path = url.Path;
    MessagingCenter.Send&lt;object, string&gt;(this, "DeepLink", path);
    return true;
}</x-guide.code>
            </x-guide.section>

            <x-guide.section title="Shared — Subscribe to deep link in your page/ViewModel">
                <x-guide.code lang="csharp">// App.xaml.cs or your NavigationService
MessagingCenter.Subscribe&lt;object, string&gt;(
    this, "DeepLink", (sender, path) =>
{
    // Navigate based on path
    if (path.StartsWith("/products/"))
    {
        var id = path.Replace("/products/", "");
        Shell.Current.GoToAsync($"//products/{id}");
    }
    else if (path.StartsWith("/profile/"))
    {
        var id = path.Replace("/profile/", "");
        Shell.Current.GoToAsync($"//profile/{id}");
    }
});</x-guide.code>
            </x-guide.section>
        </div>
    </div>

</div>

{{-- Common tips section --}}
<div class="mt-10 bg-gray-50 rounded-xl border border-gray-200 p-6">
    <h2 class="text-sm font-semibold text-gray-900 mb-4">Common troubleshooting</h2>
    <div class="space-y-3 text-sm text-gray-600">
        <div class="flex gap-3">
            <span class="text-amber-500 shrink-0">&#9888;</span>
            <div><strong class="text-gray-800">App Links / Universal Links not opening the app?</strong> Verify the AASA/assetlinks.json is served correctly — check
                <code class="bg-gray-100 px-1 rounded text-xs font-mono">https://{{ $domains->first()?->domain ?? $primaryDomain }}/.well-known/apple-app-site-association</code>
                and
                <code class="bg-gray-100 px-1 rounded text-xs font-mono">https://{{ $domains->first()?->domain ?? $primaryDomain }}/.well-known/assetlinks.json</code>.
                Both must return correct JSON over HTTPS with no redirect.
            </div>
        </div>
        <div class="flex gap-3">
            <span class="text-amber-500 shrink-0">&#9888;</span>
            <div><strong class="text-gray-800">Android — STATUS: not verified?</strong> The device must be online and able to reach your domain during install/update. Run
                <code class="bg-gray-100 px-1 rounded text-xs font-mono">adb shell pm set-app-links --package YOUR_PACKAGE 2 all</code> to force a re-verification.
            </div>
        </div>
        <div class="flex gap-3">
            <span class="text-amber-500 shrink-0">&#9888;</span>
            <div><strong class="text-gray-800">iOS — link opens in browser instead of app?</strong> Universal Links are disabled when you tap a link in the same app that hosts the entitlement. Test via Safari → Notes → another app. Also ensure your Bundle ID and Team ID exactly match what is registered in your app above.</div>
        </div>
        <div class="flex gap-3">
            <span class="text-blue-500 shrink-0">&#8505;</span>
            <div><strong class="text-gray-800">URI scheme fallback</strong> always works without verification. It is less secure (any app can claim a scheme) but useful during development and as a last-resort fallback for older OS versions.</div>
        </div>
        <div class="flex gap-3">
            <span class="text-blue-500 shrink-0">&#8505;</span>
            <div><strong class="text-gray-800">Destination path</strong> — the value you set as <em>Destination Path</em> when creating a link (e.g. <code class="bg-gray-100 px-1 rounded text-xs font-mono">/products/123</code>) is what your app receives. Design your routing around these paths.</div>
        </div>
    </div>
</div>

</x-layouts.app>
