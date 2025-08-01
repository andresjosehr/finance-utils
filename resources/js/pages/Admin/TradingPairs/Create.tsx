import { useState } from 'react'
import { Head, Link, useForm } from '@inertiajs/react'
import AuthenticatedLayout from '@/layouts/authenticated-layout'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Switch } from '@/components/ui/switch'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Separator } from '@/components/ui/separator'
import { ArrowLeft, Plus, Trash2 } from 'lucide-react'
import { toast } from 'sonner'

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        asset: '',
        fiat: '',
        is_active: true,
        collection_interval_minutes: 5,
        collection_config: {
            rows: 50,
            priority: 'high'
        },
        min_trade_amount: '',
        max_trade_amount: '',
        use_volume_sampling: false,
        volume_ranges: [100, 500, 1000, 2500, 5000],
        default_sample_volume: 500
    })

    const [volumeRange, setVolumeRange] = useState('')

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault()
        
        post(route('admin.trading-pairs.store'), {
            onSuccess: () => {
                toast.success('Trading pair created successfully')
            },
            onError: () => {
                toast.error('Failed to create trading pair')
            }
        })
    }

    const addVolumeRange = () => {
        const volume = parseFloat(volumeRange)
        if (!isNaN(volume) && volume > 0 && !data.volume_ranges.includes(volume)) {
            setData('volume_ranges', [...data.volume_ranges, volume].sort((a, b) => a - b))
            setVolumeRange('')
        }
    }

    const removeVolumeRange = (volume: number) => {
        setData('volume_ranges', data.volume_ranges.filter(v => v !== volume))
    }

    const popularAssets = ['USDT', 'BTC', 'ETH', 'BNB', 'USDC', 'FDUSD', 'ADA', 'DOT', 'SOL', 'DOGE', 'XRP']
    const popularFiats = ['VES', 'USD', 'EUR', 'ARS', 'COP', 'PEN', 'CLP', 'BRL']

    return (
        <AuthenticatedLayout>
            <Head title="Create Trading Pair" />
            
            <div className="space-y-6">
                <div className="flex items-center gap-4">
                    <Link href={route('admin.trading-pairs.index')}>
                        <Button variant="ghost" size="sm">
                            <ArrowLeft className="h-4 w-4 mr-2" />
                            Back to Trading Pairs
                        </Button>
                    </Link>
                    <div>
                        <h1 className="text-3xl font-bold tracking-tight">Create Trading Pair</h1>
                        <p className="text-muted-foreground">
                            Add a new cryptocurrency trading pair for P2P data collection
                        </p>
                    </div>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {/* Basic Configuration */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Basic Configuration</CardTitle>
                                <CardDescription>
                                    Define the trading pair and collection settings
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <Label htmlFor="asset">Asset (Crypto)</Label>
                                        <Select value={data.asset} onValueChange={(value) => setData('asset', value)}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select asset" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {popularAssets.map(asset => (
                                                    <SelectItem key={asset} value={asset}>{asset}</SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.asset && <p className="text-sm text-red-500">{errors.asset}</p>}
                                    </div>
                                    <div>
                                        <Label htmlFor="fiat">Fiat Currency</Label>
                                        <Select value={data.fiat} onValueChange={(value) => setData('fiat', value)}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select fiat" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {popularFiats.map(fiat => (
                                                    <SelectItem key={fiat} value={fiat}>{fiat}</SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.fiat && <p className="text-sm text-red-500">{errors.fiat}</p>}
                                    </div>
                                </div>

                                <div className="flex items-center justify-between">
                                    <div className="space-y-0.5">
                                        <Label>Active Status</Label>
                                        <p className="text-sm text-muted-foreground">
                                            Enable data collection for this pair
                                        </p>
                                    </div>
                                    <Switch
                                        checked={data.is_active}
                                        onCheckedChange={(checked) => setData('is_active', checked)}
                                    />
                                </div>

                                <div>
                                    <Label htmlFor="interval">Collection Interval (minutes)</Label>
                                    <Input
                                        id="interval"
                                        type="number"
                                        min="1"
                                        max="1440"
                                        value={data.collection_interval_minutes}
                                        onChange={(e) => setData('collection_interval_minutes', parseInt(e.target.value))}
                                    />
                                    {errors.collection_interval_minutes && (
                                        <p className="text-sm text-red-500">{errors.collection_interval_minutes}</p>
                                    )}
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <Label htmlFor="rows">API Rows per Request</Label>
                                        <Input
                                            id="rows"
                                            type="number"
                                            min="1"
                                            max="100"
                                            value={data.collection_config.rows}
                                            onChange={(e) => setData('collection_config', {
                                                ...data.collection_config,
                                                rows: parseInt(e.target.value)
                                            })}
                                        />
                                    </div>
                                    <div>
                                        <Label htmlFor="priority">Priority</Label>
                                        <Select 
                                            value={data.collection_config.priority} 
                                            onValueChange={(value) => setData('collection_config', {
                                                ...data.collection_config,
                                                priority: value
                                            })}
                                        >
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="low">Low</SelectItem>
                                                <SelectItem value="medium">Medium</SelectItem>
                                                <SelectItem value="high">High</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Volume Sampling Configuration */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Volume Sampling Configuration</CardTitle>
                                <CardDescription>
                                    Configure how market data is collected across different transaction volumes
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex items-center justify-between">
                                    <div className="space-y-0.5">
                                        <Label>Enable Volume Sampling</Label>
                                        <p className="text-sm text-muted-foreground">
                                            Collect data across multiple volume ranges for better price distribution
                                        </p>
                                    </div>
                                    <Switch
                                        checked={data.use_volume_sampling}
                                        onCheckedChange={(checked) => setData('use_volume_sampling', checked)}
                                    />
                                </div>

                                {data.use_volume_sampling ? (
                                    <div className="space-y-4">
                                        <div>
                                            <Label>Volume Ranges</Label>
                                            <div className="flex gap-2 mt-2">
                                                <Input
                                                    type="number"
                                                    placeholder="Enter volume amount"
                                                    value={volumeRange}
                                                    onChange={(e) => setVolumeRange(e.target.value)}
                                                    onKeyDown={(e) => {
                                                        if (e.key === 'Enter') {
                                                            e.preventDefault()
                                                            addVolumeRange()
                                                        }
                                                    }}
                                                />
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    onClick={addVolumeRange}
                                                >
                                                    <Plus className="h-4 w-4" />
                                                </Button>
                                            </div>
                                            <div className="flex flex-wrap gap-2 mt-2">
                                                {data.volume_ranges.map((volume) => (
                                                    <div
                                                        key={volume}
                                                        className="flex items-center gap-1 bg-secondary px-2 py-1 rounded-md text-sm"
                                                    >
                                                        {volume}
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="sm"
                                                            className="h-4 w-4 p-0"
                                                            onClick={() => removeVolumeRange(volume)}
                                                        >
                                                            <Trash2 className="h-3 w-3" />
                                                        </Button>
                                                    </div>
                                                ))}
                                            </div>
                                            {errors.volume_ranges && (
                                                <p className="text-sm text-red-500">{errors.volume_ranges}</p>
                                            )}
                                        </div>
                                    </div>
                                ) : (
                                    <div>
                                        <Label htmlFor="default_volume">Default Sample Volume</Label>
                                        <Input
                                            id="default_volume"
                                            type="number"
                                            min="1"
                                            value={data.default_sample_volume}
                                            onChange={(e) => setData('default_sample_volume', parseFloat(e.target.value))}
                                        />
                                        <p className="text-sm text-muted-foreground mt-1">
                                            Transaction amount used for single-point data collection
                                        </p>
                                        {errors.default_sample_volume && (
                                            <p className="text-sm text-red-500">{errors.default_sample_volume}</p>
                                        )}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {/* Trade Amount Limits (Optional) */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Trade Amount Limits (Optional)</CardTitle>
                            <CardDescription>
                                Set minimum and maximum trade amounts for this pair
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <Label htmlFor="min_amount">Minimum Trade Amount</Label>
                                    <Input
                                        id="min_amount"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={data.min_trade_amount}
                                        onChange={(e) => setData('min_trade_amount', e.target.value)}
                                    />
                                    {errors.min_trade_amount && (
                                        <p className="text-sm text-red-500">{errors.min_trade_amount}</p>
                                    )}
                                </div>
                                <div>
                                    <Label htmlFor="max_amount">Maximum Trade Amount</Label>
                                    <Input
                                        id="max_amount"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        value={data.max_trade_amount}
                                        onChange={(e) => setData('max_trade_amount', e.target.value)}
                                    />
                                    {errors.max_trade_amount && (
                                        <p className="text-sm text-red-500">{errors.max_trade_amount}</p>
                                    )}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Separator />

                    <div className="flex items-center justify-end gap-4">
                        <Link href={route('admin.trading-pairs.index')}>
                            <Button variant="outline">Cancel</Button>
                        </Link>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Creating...' : 'Create Trading Pair'}
                        </Button>
                    </div>
                </form>
            </div>
        </AuthenticatedLayout>
    )
}