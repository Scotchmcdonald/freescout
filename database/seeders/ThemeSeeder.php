<?php

namespace Database\Seeders;

use App\Models\Theme;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ThemeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $themes = [
            // Default (Deep Forest)
            'default' => [
                'name' => 'Default (Deep Forest)',
                'light' => [
                    'primary' => ['50' => '#f0fdf4', '100' => '#dcfce7', '500' => '#22c55e', '600' => '#16a34a', '700' => '#15803d'],
                    'bg' => ['main' => '#f0fdf4', 'card' => '#ffffff', 'input' => '#ffffff', 'hover' => '#dcfce7'],
                    'text' => ['main' => '#064e3b', 'muted' => '#374151', 'inverted' => '#ffffff'],
                    'border' => '#bbf7d0',
                    'status' => [
                        'success' => ['bg' => '#dcfce7', 'text' => '#166534'],
                        'warning' => ['bg' => '#fef9c3', 'text' => '#854d0e'],
                        'info' => ['bg' => '#dbeafe', 'text' => '#1e40af'],
                        'error' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
                    ],
                    'nav' => ['bg' => '#f0fdf4', 'text' => '#064e3b', 'border' => '#bbf7d0'],
                ],
                'dark' => [
                    'primary' => ['50' => '#064e3b', '100' => '#065f46', '500' => '#4ade80', '600' => '#22c55e', '700' => '#16a34a'],
                    'bg' => ['main' => '#001510', 'card' => '#022c22', 'input' => '#064e3b', 'hover' => '#064e3b'],
                    'text' => ['main' => '#ecfdf5', 'muted' => '#a7f3d0', 'inverted' => '#022c22'],
                    'border' => '#064e3b',
                    'status' => [
                        'success' => ['bg' => '#064e3b', 'text' => '#4ade80'],
                        'warning' => ['bg' => '#422006', 'text' => '#facc15'],
                        'info' => ['bg' => '#1e3a8a', 'text' => '#60a5fa'],
                        'error' => ['bg' => '#450a0a', 'text' => '#fca5a5'],
                    ],
                    'nav' => ['bg' => '#022c22', 'text' => '#ecfdf5', 'border' => '#064e3b'],
                ],
            ],
            // SynthWave '84 (Replaces Blue)
            'synthwave' => [
                'name' => 'SynthWave \'84',
                'light' => [
                    'primary' => ['50' => '#fff7ed', '100' => '#ffedd5', '500' => '#f97e72', '600' => '#ea580c', '700' => '#c2410c'],
                    'bg' => ['main' => '#fdf4ff', 'card' => '#ffffff', 'input' => '#ffffff', 'hover' => '#fae8ff'],
                    'text' => ['main' => '#4a044e', 'muted' => '#701a75', 'inverted' => '#ffffff'],
                    'border' => '#f0abfc',
                    'status' => [
                        'success' => ['bg' => '#dcfce7', 'text' => '#166534'],
                        'warning' => ['bg' => '#ffedd5', 'text' => '#9a3412'],
                        'info' => ['bg' => '#fae8ff', 'text' => '#86198f'],
                        'error' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
                    ],
                    'nav' => ['bg' => '#2b213a', 'text' => '#f0abfc', 'border' => '#f0abfc'],
                ],
                'dark' => [
                    'primary' => ['50' => '#4a044e', '100' => '#701a75', '500' => '#f97e72', '600' => '#e66b5f', '700' => '#d1584d'],
                    'bg' => ['main' => '#2b213a', 'card' => '#241b2f', 'input' => '#372948', 'hover' => '#372948'],
                    'text' => ['main' => '#ffffff', 'muted' => '#b3b0c2', 'inverted' => '#2b213a'],
                    'border' => '#372948',
                    'status' => [
                        'success' => ['bg' => '#064e3b', 'text' => '#4ade80'],
                        'warning' => ['bg' => '#431407', 'text' => '#fb923c'],
                        'info' => ['bg' => '#4a044e', 'text' => '#e879f9'],
                        'error' => ['bg' => '#450a0a', 'text' => '#fca5a5'],
                    ],
                    'nav' => ['bg' => '#241b2f', 'text' => '#f0abfc', 'border' => '#372948'],
                ],
            ],
            // Classic
            'classic' => [
                'name' => 'Classic',
                'light' => [
                    'primary' => ['50' => '#eff6ff', '100' => '#dbeafe', '500' => '#3b82f6', '600' => '#2563eb', '700' => '#1d4ed8'],
                    'bg' => ['main' => '#f3f4f6', 'card' => '#ffffff', 'input' => '#ffffff', 'hover' => '#f9fafb'],
                    'text' => ['main' => '#111827', 'muted' => '#4b5563', 'inverted' => '#ffffff'],
                    'border' => '#e5e7eb',
                    'status' => [
                        'success' => ['bg' => '#dcfce7', 'text' => '#166534'],
                        'warning' => ['bg' => '#fef9c3', 'text' => '#854d0e'],
                        'info' => ['bg' => '#dbeafe', 'text' => '#1e40af'],
                        'error' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
                    ],
                    'nav' => ['bg' => '#ffffff', 'text' => '#111827', 'border' => '#e5e7eb'],
                ],
                'dark' => [
                    'primary' => ['50' => '#1e293b', '100' => '#334155', '500' => '#3b82f6', '600' => '#2563eb', '700' => '#1d4ed8'],
                    'bg' => ['main' => '#111827', 'card' => '#1f2937', 'input' => '#374151', 'hover' => '#374151'],
                    'text' => ['main' => '#f9fafb', 'muted' => '#9ca3af', 'inverted' => '#111827'],
                    'border' => '#374151',
                    'status' => [
                        'success' => ['bg' => '#064e3b', 'text' => '#4ade80'],
                        'warning' => ['bg' => '#422006', 'text' => '#facc15'],
                        'info' => ['bg' => '#1e3a8a', 'text' => '#60a5fa'],
                        'error' => ['bg' => '#450a0a', 'text' => '#fca5a5'],
                    ],
                    'nav' => ['bg' => '#1f2937', 'text' => '#f9fafb', 'border' => '#374151'],
                ],
            ],
            // Monokai Pro (Replaces Green)
            'monokai' => [
                'name' => 'Monokai Pro',
                'light' => [
                    'primary' => ['50' => '#fff1f2', '100' => '#ffe4e6', '500' => '#f92672', '600' => '#e11d48', '700' => '#be123c'],
                    'bg' => ['main' => '#fff1f2', 'card' => '#ffffff', 'input' => '#ffffff', 'hover' => '#ffe4e6'],
                    'text' => ['main' => '#881337', 'muted' => '#9f1239', 'inverted' => '#ffffff'],
                    'border' => '#fda4af',
                    'status' => [
                        'success' => ['bg' => '#dcfce7', 'text' => '#166534'],
                        'warning' => ['bg' => '#fef9c3', 'text' => '#854d0e'],
                        'info' => ['bg' => '#dbeafe', 'text' => '#1e40af'],
                        'error' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
                    ],
                    'nav' => ['bg' => '#2d2a2e', 'text' => '#fcfcfa', 'border' => '#5b595c'],
                ],
                'dark' => [
                    'primary' => ['50' => '#881337', '100' => '#9f1239', '500' => '#f92672', '600' => '#fb7185', '700' => '#f43f5e'],
                    'bg' => ['main' => '#2d2a2e', 'card' => '#403e41', 'input' => '#5b595c', 'hover' => '#5b595c'],
                    'text' => ['main' => '#fcfcfa', 'muted' => '#939293', 'inverted' => '#2d2a2e'],
                    'border' => '#5b595c',
                    'status' => [
                        'success' => ['bg' => '#2d2a2e', 'text' => '#a6e22e'],
                        'warning' => ['bg' => '#2d2a2e', 'text' => '#fd971f'],
                        'info' => ['bg' => '#2d2a2e', 'text' => '#66d9ef'],
                        'error' => ['bg' => '#2d2a2e', 'text' => '#f92672'],
                    ],
                    'nav' => ['bg' => '#403e41', 'text' => '#fcfcfa', 'border' => '#5b595c'],
                ],
            ],
            // Purple
            'purple' => [
                'name' => 'Purple',
                'light' => [
                    'primary' => ['50' => '#faf5ff', '100' => '#f3e8ff', '500' => '#a855f7', '600' => '#9333ea', '700' => '#7e22ce'],
                    'bg' => ['main' => '#faf5ff', 'card' => '#ffffff', 'input' => '#ffffff', 'hover' => '#f3e8ff'],
                    'text' => ['main' => '#581c87', 'muted' => '#4b5563', 'inverted' => '#ffffff'],
                    'border' => '#e9d5ff',
                    'status' => [
                        'success' => ['bg' => '#dcfce7', 'text' => '#166534'],
                        'warning' => ['bg' => '#fef9c3', 'text' => '#854d0e'],
                        'info' => ['bg' => '#dbeafe', 'text' => '#1e40af'],
                        'error' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
                    ],
                    'nav' => ['bg' => '#f3e8ff', 'text' => '#581c87', 'border' => '#e9d5ff'],
                ],
                'dark' => [
                    'primary' => ['50' => '#3b0764', '100' => '#581c87', '500' => '#c084fc', '600' => '#a855f7', '700' => '#9333ea'],
                    'bg' => ['main' => '#2e1065', 'card' => '#4c1d95', 'input' => '#5b21b6', 'hover' => '#5b21b6'],
                    'text' => ['main' => '#faf5ff', 'muted' => '#e9d5ff', 'inverted' => '#2e1065'],
                    'border' => '#5b21b6',
                    'status' => [
                        'success' => ['bg' => '#064e3b', 'text' => '#4ade80'],
                        'warning' => ['bg' => '#422006', 'text' => '#facc15'],
                        'info' => ['bg' => '#1e3a8a', 'text' => '#60a5fa'],
                        'error' => ['bg' => '#450a0a', 'text' => '#fca5a5'],
                    ],
                    'nav' => ['bg' => '#4c1d95', 'text' => '#faf5ff', 'border' => '#5b21b6'],
                ],
            ],
            // Solarized
            'solarized' => [
                'name' => 'Solarized',
                'light' => [
                    'primary' => ['50' => '#fdf6e3', '100' => '#eee8d5', '500' => '#268bd2', '600' => '#2aa198', '700' => '#268bd2'],
                    'bg' => ['main' => '#fdf6e3', 'card' => '#eee8d5', 'input' => '#fdf6e3', 'hover' => '#e6dfc8'],
                    'text' => ['main' => '#657b83', 'muted' => '#93a1a1', 'inverted' => '#fdf6e3'],
                    'border' => '#eee8d5',
                    'status' => [
                        'success' => ['bg' => '#dcfce7', 'text' => '#166534'],
                        'warning' => ['bg' => '#fef9c3', 'text' => '#854d0e'],
                        'info' => ['bg' => '#dbeafe', 'text' => '#1e40af'],
                        'error' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
                    ],
                    'nav' => ['bg' => '#eee8d5', 'text' => '#657b83', 'border' => '#93a1a1'],
                ],
                'dark' => [
                    'primary' => ['50' => '#002b36', '100' => '#073642', '500' => '#268bd2', '600' => '#2aa198', '700' => '#268bd2'],
                    'bg' => ['main' => '#002b36', 'card' => '#073642', 'input' => '#002b36', 'hover' => '#094b59'],
                    'text' => ['main' => '#839496', 'muted' => '#586e75', 'inverted' => '#002b36'],
                    'border' => '#073642',
                    'status' => [
                        'success' => ['bg' => '#073642', 'text' => '#859900'],
                        'warning' => ['bg' => '#073642', 'text' => '#b58900'],
                        'info' => ['bg' => '#073642', 'text' => '#268bd2'],
                        'error' => ['bg' => '#073642', 'text' => '#dc322f'],
                    ],
                    'nav' => ['bg' => '#073642', 'text' => '#839496', 'border' => '#002b36'],
                ],
            ],
            // Minimal Kiwi
            'minimal_kiwi' => [
                'name' => 'Minimal Kiwi',
                'light' => [
                    'primary' => ['50' => '#f7fee7', '100' => '#ecfccb', '500' => '#84cc16', '600' => '#65a30d', '700' => '#4d7c0f'],
                    'bg' => ['main' => '#f7fee7', 'card' => '#ffffff', 'input' => '#ffffff', 'hover' => '#ecfccb'],
                    'text' => ['main' => '#365314', 'muted' => '#4d7c0f', 'inverted' => '#ffffff'],
                    'border' => '#d9f99d',
                    'status' => [
                        'success' => ['bg' => '#ecfccb', 'text' => '#365314'],
                        'warning' => ['bg' => '#fef9c3', 'text' => '#854d0e'],
                        'info' => ['bg' => '#dbeafe', 'text' => '#1e40af'],
                        'error' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
                    ],
                    'nav' => ['bg' => '#f7fee7', 'text' => '#365314', 'border' => '#d9f99d'],
                ],
                'dark' => [
                    'primary' => ['50' => '#365314', '100' => '#4d7c0f', '500' => '#84cc16', '600' => '#65a30d', '700' => '#4d7c0f'],
                    'bg' => ['main' => '#1a2e05', 'card' => '#365314', 'input' => '#4d7c0f', 'hover' => '#4d7c0f'],
                    'text' => ['main' => '#ecfccb', 'muted' => '#bef264', 'inverted' => '#1a2e05'],
                    'border' => '#4d7c0f',
                    'status' => [
                        'success' => ['bg' => '#365314', 'text' => '#84cc16'],
                        'warning' => ['bg' => '#422006', 'text' => '#facc15'],
                        'info' => ['bg' => '#1e3a8a', 'text' => '#60a5fa'],
                        'error' => ['bg' => '#450a0a', 'text' => '#fca5a5'],
                    ],
                    'nav' => ['bg' => '#365314', 'text' => '#ecfccb', 'border' => '#4d7c0f'],
                ],
            ],
            // Tokyo Night Pro
            'tokyo_night' => [
                'name' => 'Tokyo Night Pro',
                'light' => [
                    'primary' => ['50' => '#e9effb', '100' => '#d2dff7', '500' => '#3760bf', '600' => '#2f52a3', '700' => '#274487'],
                    'bg' => ['main' => '#e1e2e7', 'card' => '#ffffff', 'input' => '#ffffff', 'hover' => '#d0d5e3'],
                    'text' => ['main' => '#343b58', 'muted' => '#565f89', 'inverted' => '#ffffff'],
                    'border' => '#d0d5e3',
                    'status' => [
                        'success' => ['bg' => '#ecfccb', 'text' => '#365314'],
                        'warning' => ['bg' => '#fef9c3', 'text' => '#854d0e'],
                        'info' => ['bg' => '#dbeafe', 'text' => '#1e40af'],
                        'error' => ['bg' => '#fee2e2', 'text' => '#991b1b'],
                    ],
                    'nav' => ['bg' => '#d5d6db', 'text' => '#343b58', 'border' => '#c0c1c7'],
                ],
                'dark' => [
                    'primary' => ['50' => '#1d2433', '100' => '#28344a', '500' => '#7aa2f7', '600' => '#6892e8', '700' => '#5682d9'],
                    'bg' => ['main' => '#1a1b26', 'card' => '#1f2335', 'input' => '#16161e', 'hover' => '#292e42'],
                    'text' => ['main' => '#c0caf5', 'muted' => '#565f89', 'inverted' => '#15161e'],
                    'border' => '#292e42',
                    'status' => [
                        'success' => ['bg' => '#1a1b26', 'text' => '#9ece6a'],
                        'warning' => ['bg' => '#1a1b26', 'text' => '#e0af68'],
                        'info' => ['bg' => '#1a1b26', 'text' => '#7dcfff'],
                        'error' => ['bg' => '#1a1b26', 'text' => '#f7768e'],
                    ],
                    'nav' => ['bg' => '#16161e', 'text' => '#c0caf5', 'border' => '#101014'],
                ],
            ],
            // Rosé Pine
            'rose_pine' => [
                'name' => 'Rosé Pine',
                'light' => [
                    'primary' => ['50' => '#fffaf3', '100' => '#f2e9e1', '500' => '#d7827e', '600' => '#b4637a', '700' => '#907aa9'],
                    'bg' => ['main' => '#faf4ed', 'card' => '#fffaf3', 'input' => '#ffffff', 'hover' => '#f2e9e1'],
                    'text' => ['main' => '#575279', 'muted' => '#9893a5', 'inverted' => '#faf4ed'],
                    'border' => '#dfdad9',
                    'status' => [
                        'success' => ['bg' => '#ecfccb', 'text' => '#56949f'],
                        'warning' => ['bg' => '#fef9c3', 'text' => '#ea9d34'],
                        'info' => ['bg' => '#dbeafe', 'text' => '#286983'],
                        'error' => ['bg' => '#fee2e2', 'text' => '#b4637a'],
                    ],
                    'nav' => ['bg' => '#fffaf3', 'text' => '#575279', 'border' => '#dfdad9'],
                ],
                'dark' => [
                    'primary' => ['50' => '#21202e', '100' => '#403d52', '500' => '#ebbcba', '600' => '#e0a6a4', '700' => '#d1918f'],
                    'bg' => ['main' => '#191724', 'card' => '#1f1d2e', 'input' => '#26233a', 'hover' => '#26233a'],
                    'text' => ['main' => '#e0def4', 'muted' => '#908caa', 'inverted' => '#191724'],
                    'border' => '#26233a',
                    'status' => [
                        'success' => ['bg' => '#191724', 'text' => '#9ccfd8'],
                        'warning' => ['bg' => '#191724', 'text' => '#f6c177'],
                        'info' => ['bg' => '#191724', 'text' => '#31748f'],
                        'error' => ['bg' => '#191724', 'text' => '#eb6f92'],
                    ],
                    'nav' => ['bg' => '#1f1d2e', 'text' => '#e0def4', 'border' => '#26233a'],
                ],
            ],
        ];

        foreach ($themes as $key => $data) {
            Theme::updateOrCreate(
                ['name' => $key],
                [
                    'title' => $data['name'],
                    'config' => $data,
                    'is_system' => true,
                    'created_by' => 1, // Assuming admin user ID 1
                ]
            );

            // Ensure theme directory exists to prevent optimization errors
            $themePath = base_path('themes/' . $key . '/views');
            if (!File::exists($themePath)) {
                File::makeDirectory($themePath, 0755, true);
            }
        }
    }
}
