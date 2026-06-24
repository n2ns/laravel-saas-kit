<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Filament\Resources\ProductResource\RelationManagers\PlansRelationManager;
use App\Filament\Resources\ProductResource\RelationManagers\TranslationsRelationManager;
use App\Models\Product;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static string|UnitEnum|null $navigationGroup = '商业运营';

    protected static ?string $navigationLabel = '上架管理';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(3) // 2:1 比例布局
            ->components([
                // 左侧主内容栏
                Group::make()
                    ->columnSpan(2)
                    ->schema([
                        Section::make('基本信息')
                            ->schema([
                                Select::make('catalog_item_id')
                                    ->label('产品资料')
                                    ->relationship('catalogItem', 'code')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                TextInput::make('code')
                                    ->label('产品编码')
                                    ->required()
                                    ->unique(ignoreRecord: true)
                                    ->maxLength(255),
                                Placeholder::make('catalog_name')
                                    ->label('资料名称')
                                    ->content(fn (?Product $record): string => $record?->name ?? '-'),
                            ])->columns(2),
                    ]),

                // 右侧侧边栏
                Group::make()
                    ->columnSpan(1)
                    ->schema([
                        Section::make('状态与排序')
                            ->schema([
                                Toggle::make('is_active')
                                    ->label('启用状态')
                                    ->default(true),
                                TextInput::make('sort_order')
                                    ->label('显示权重')
                                    ->numeric()
                                    ->default(0),
                                Select::make('pause_reason')
                                    ->label('订阅控制')
                                    ->options([
                                        null => '✅ 正常 (允许订阅)',
                                        'maintenance' => '🔧 维护中',
                                        'payment_upgrade' => '💳 支付系统升级',
                                        'coming_soon' => '🚀 即将上线',
                                        'region_restricted' => '🌍 区域限制',
                                    ])
                                    ->placeholder('正常')
                                    ->helperText('设置非空值将暂停新用户订阅'),
                            ]),

                        Section::make('高级集成')
                            ->schema([
                                TextInput::make('stripe_product_id')
                                    ->label('Stripe 产品 ID')
                                    ->maxLength(255),
                                TextInput::make('pricing_page_url')
                                    ->label('外部价格页')
                                    ->url()
                                    ->maxLength(255),
                                TextInput::make('mcp_server_url')
                                    ->label('MCP 服务器地址')
                                    ->url()
                                    ->maxLength(255),
                                TextInput::make('mcp_api_key')
                                    ->label('MCP API 密钥')
                                    ->password()
                                    ->revealable()
                                    ->maxLength(255),
                                Textarea::make('metadata')
                                    ->label('元数据 (JSON)')
                                    ->helperText('额外 JSON 数据 (如数据来源、统计指标等)')
                                    ->rows(5)
                                    ->formatStateUsing(fn ($state) => json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
                                    ->dehydrateStateUsing(fn ($state) => json_decode($state, true) ?? []),
                            ])->collapsed(),

                        Section::make('记录摘要')
                            ->schema([
                                Placeholder::make('created_at')
                                    ->label('创建时间')
                                    ->content(fn (Product $record): ?string => $record->created_at?->translatedFormat('Y-m-d H:i') ?? '-'),

                                Placeholder::make('updated_at')
                                    ->label('最后修改')
                                    ->content(fn (Product $record): ?string => $record->updated_at?->diffForHumans() ?? '-'),
                            ])
                            ->visible(fn ($record) => $record !== null),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(null)
            ->recordAction(null)
            ->columns([
                TextColumn::make('code')
                    ->label('产品编码')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->label('名称'),
                TextColumn::make('card_tag')
                    ->label('标签'),
                IconColumn::make('is_active')
                    ->label('启用')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->label('权重')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('pause_reason')
                    ->label('订阅状态')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => $state ? match ($state) {
                        'maintenance' => '🔧 维护',
                        'payment_upgrade' => '💳 升级',
                        'coming_soon' => '🚀 预热',
                        'region_restricted' => '🌍 受限',
                        default => $state,
                    } : '✅ 正常')
                    ->color(fn (?string $state): string => $state ? 'warning' : 'success'),
                TextColumn::make('translations_count')
                    ->label('翻译数')
                    ->counts('translations'),
                TextColumn::make('plans_count')
                    ->label('方案数')
                    ->counts('plans'),
                TextColumn::make('updated_at')
                    ->label('最后更新')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('按状态过滤'),
            ])
            ->recordActions([
                EditAction::make()
                    ->label('编辑'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TranslationsRelationManager::class,
            PlansRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withLocalizedTranslations(app()->getLocale());
    }
}
