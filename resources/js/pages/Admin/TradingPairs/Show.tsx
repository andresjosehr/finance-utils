import { Head, Link } from '@inertiajs/react'
import AuthenticatedLayout from '@/layouts/authenticated-layout'
import { Button } from '@/components/ui/button'
import { Badge } from '@/components/ui/badge'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Separator } from '@/components/ui/separator'
import { ArrowLeft, Edit, Activity, Clock, TrendingUp, Database } from 'lucide-react'

interface TradingPair {
    id: number
    asset: string
    fiat: string
    pair_symbol: string
    is_active: boolean
    collection_interval_minutes: number
    collection_config: any
    min_trade_amount: number | null
    max_trade_amount: number | null
    volume_ranges: number[] | null
    use_volume_sampling: boolean
    default_sample_volume: number
    collection_status: any
    recent_snapshots: Array<{
        id: number
        trade_type: string
        collected_at: string
        total_ads: number
        data_quality_score: number
    }>
}

interface Props {
    pair: TradingPair
}

export default function Show({ pair }: Props) {
    const getStatusBadge = () => {
        if (!pair.is_active) {
            return <Badge variant="secondary">Inactive</Badge>
        }
        
        const status = pair.collection_status
        const minutesSinceCollection = status.minutes_since_last_collection
        
        if (minutesSinceCollection === null) {
            return <Badge variant="outline">Never Collected</Badge>
        }
        
        if (minutesSinceCollection < 10) {
            return <Badge variant="default">Active</Badge>
        } else if (minutesSinceCollection < 30) {
            return <Badge variant="outline">Recent</Badge>
        } else {
            return <Badge variant="destructive">Stale</Badge>
        }
    }

    const getQualityBadge = (score: number) => {
        if (score >= 0.8) {
            return <Badge variant="default">High</Badge>
        } else if (score >= 0.5) {
            return <Badge variant="outline">Medium</Badge>
        } else {
            return <Badge variant="destructive">Low</Badge>
        }
    }

    return (
        <AuthenticatedLayout>
            <Head title={`${pair.pair_symbol} Details`} />
            
            <div className="space-y-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link href={route('admin.trading-pairs.index')}>
                            <Button variant="ghost" size="sm">
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Back to Trading Pairs
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-3xl font-bold tracking-tight">{pair.pair_symbol}</h1>
                            <p className="text-muted-foreground">
                                {pair.asset} to {pair.fiat} trading pair details and collection status
                            </p>
                        </div>
                    </div>
                    <Link href={route('admin.trading-pairs.edit', pair.id)}>
                        <Button>
                            <Edit className="h-4 w-4 mr-2" />
                            Edit Configuration
                        </Button>
                    </Link>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Status</CardTitle>
                            <Activity className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold mb-1">
                                {getStatusBadge()}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                {pair.is_active ? 'Collecting data' : 'Data collection disabled'}
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Collection Interval</CardTitle>
                            <Clock className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{pair.collection_interval_minutes}m</div>
                            <p className="text-xs text-muted-foreground">
                                Data collected every {pair.collection_interval_minutes} minutes
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Volume Sampling</CardTitle>
                            <TrendingUp className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                <Badge variant={pair.use_volume_sampling ? "default" : "secondary"}>
                                    {pair.use_volume_sampling ? "Enabled" : "Disabled"}
                                </Badge>
                            </div>
                            <p className="text-xs text-muted-foreground">
                                {pair.use_volume_sampling 
                                    ? `${pair.volume_ranges?.length || 0} volume ranges`
                                    : `Default: ${pair.default_sample_volume}`
                                }
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Recent Snapshots</CardTitle>
                            <Database className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{pair.recent_snapshots.length}</div>
                            <p className="text-xs text-muted-foreground">
                                In the last 24 hours
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    {/* Configuration Details */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Configuration</CardTitle>
                            <CardDescription>
                                Current trading pair and collection settings
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">Asset</p>
                                    <p className="text-lg font-semibold">{pair.asset}</p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">Fiat Currency</p>
                                    <p className="text-lg font-semibold">{pair.fiat}</p>
                                </div>
                            </div>

                            <Separator />

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">API Rows per Request</p>
                                    <p className="text-lg font-semibold">{pair.collection_config?.rows || 'Not set'}</p>
                                </div>
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">Priority</p>
                                    <Badge variant="outline">
                                        {pair.collection_config?.priority || 'Medium'}
                                    </Badge>
                                </div>
                            </div>

                            {(pair.min_trade_amount || pair.max_trade_amount) && (
                                <>
                                    <Separator />
                                    <div className="grid grid-cols-2 gap-4">
                                        {pair.min_trade_amount && (
                                            <div>
                                                <p className="text-sm font-medium text-muted-foreground">Min Trade Amount</p>
                                                <p className="text-lg font-semibold">{pair.min_trade_amount}</p>
                                            </div>
                                        )}
                                        {pair.max_trade_amount && (
                                            <div>
                                                <p className="text-sm font-medium text-muted-foreground">Max Trade Amount</p>
                                                <p className="text-lg font-semibold">{pair.max_trade_amount}</p>
                                            </div>
                                        )}
                                    </div>
                                </>
                            )}
                        </CardContent>
                    </Card>

                    {/* Volume Sampling Configuration */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Volume Sampling</CardTitle>
                            <CardDescription>
                                Current volume sampling configuration for price diversity
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-center justify-between">
                                <p className="text-sm font-medium text-muted-foreground">Volume Sampling</p>
                                <Badge variant={pair.use_volume_sampling ? "default" : "secondary"}>
                                    {pair.use_volume_sampling ? "Enabled" : "Disabled"}
                                </Badge>
                            </div>

                            <Separator />

                            {pair.use_volume_sampling ? (
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground mb-2">Volume Ranges</p>
                                    <div className="flex flex-wrap gap-2">
                                        {pair.volume_ranges?.map((volume) => (
                                            <Badge key={volume} variant="outline">
                                                {volume}
                                            </Badge>
                                        )) || <p className="text-sm text-muted-foreground">No ranges configured</p>}
                                    </div>
                                </div>
                            ) : (
                                <div>
                                    <p className="text-sm font-medium text-muted-foreground">Default Sample Volume</p>
                                    <p className="text-lg font-semibold">{pair.default_sample_volume}</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Collection Status */}
                <Card>
                    <CardHeader>
                        <CardTitle>Collection Status</CardTitle>
                        <CardDescription>
                            Current data collection status and timing information
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <p className="text-sm font-medium text-muted-foreground">Last Collection</p>
                                <p className="text-lg font-semibold">
                                    {pair.collection_status.last_collection_at
                                        ? new Date(pair.collection_status.last_collection_at).toLocaleString()
                                        : 'Never'
                                    }
                                </p>
                            </div>
                            <div>
                                <p className="text-sm font-medium text-muted-foreground">Minutes Since Last</p>
                                <p className="text-lg font-semibold">
                                    {pair.collection_status.minutes_since_last_collection ?? 'N/A'}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm font-medium text-muted-foreground">Next Collection</p>
                                <p className="text-lg font-semibold">
                                    {pair.collection_status.seconds_until_next_collection > 0
                                        ? `${Math.ceil(pair.collection_status.seconds_until_next_collection / 60)}m`
                                        : 'Due now'
                                    }
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Recent Market Snapshots */}
                <Card>
                    <CardHeader>
                        <CardTitle>Recent Market Snapshots</CardTitle>
                        <CardDescription>
                            Latest data collection results for this trading pair
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {pair.recent_snapshots.length > 0 ? (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Trade Type</TableHead>
                                        <TableHead>Collected At</TableHead>
                                        <TableHead>Total Ads</TableHead>
                                        <TableHead>Data Quality</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {pair.recent_snapshots.map((snapshot) => (
                                        <TableRow key={snapshot.id}>
                                            <TableCell>
                                                <Badge variant={snapshot.trade_type === 'BUY' ? 'default' : 'secondary'}>
                                                    {snapshot.trade_type}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {new Date(snapshot.collected_at).toLocaleString()}
                                            </TableCell>
                                            <TableCell>{snapshot.total_ads}</TableCell>
                                            <TableCell>
                                                {getQualityBadge(snapshot.data_quality_score)}
                                                <span className="ml-2 text-sm text-muted-foreground">
                                                    {(snapshot.data_quality_score * 100).toFixed(1)}%
                                                </span>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        ) : (
                            <div className="text-center py-8">
                                <p className="text-muted-foreground">
                                    No recent snapshots found for this trading pair.
                                </p>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    )
}